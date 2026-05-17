#!/usr/bin/env python3
"""
PaddleOCR wrapper for dental outpatient WORK LOG (口腔门诊工作日志) tables.

Unlike ocr_service.py (single-form key/value extraction), this script
reconstructs a multi-row table:

  1. Run OCR, get text boxes with bounding boxes.
  2. Group boxes into visual rows by y-coordinate proximity.
  3. Detect the header row (row with the most header keywords).
  4. Derive column x-boundaries from header cell centers.
  5. Assign every data-row box to a column by its x-center.
  6. Emit structured rows keyed by canonical field names.

Usage:
    python3 worklog_ocr.py <image_path>

Output (stdout):
    JSON: {
      "header_found": bool,
      "columns": [canonical field keys, left-to-right],
      "rows": [ { "<field>": "<value>", ... }, ... ],
      "avg_confidence": float,
      "raw_lines": [ "row text", ... ]   # fallback / debugging
    }

Errors go to stderr, never pollute stdout JSON.

Requirements:
    pip3 install paddleocr Pillow
"""

import sys
import json
import os
import re

os.environ.setdefault('PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK', 'True')

MAX_IMAGE_SIDE = 960

# Canonical field key -> header keywords that map to it.
# Order here also defines the preferred output column order.
#
# '初诊' and '复诊' are kept as SEPARATE internal keys so that when a work-log
# table has two sub-columns (one per visit type), we can tell which sub-column
# a checkmark falls into by its x-position.  They are merged back into the
# single 'visit_type' key before JSON is emitted.
HEADER_FIELDS = [
    ('log_date',        ['日期', '就诊日期', '时间']),
    ('patient_name',    ['姓名', '患者姓名', '病人姓名']),
    ('gender',          ['性别']),
    ('age',             ['年龄']),
    ('visit_initial',   ['初诊']),
    ('visit_revisit',   ['复诊']),
    ('visit_type',      ['初复诊', '初/复诊', '初诊复诊']),
    ('phone',           ['电话', '联系电话', '手机', '手机号']),
    ('tooth_position',  ['牙位', '牙位号', '患牙']),
    ('diagnosis',       ['诊断', '初步诊断']),
    ('prescription',    ['处方', '处置', '处理', '治疗']),
    ('doctor_name_raw', ['医师', '医生', '接诊医师']),
    ('amount',          ['收费', '金额', '费用', '收费金额', '应收']),
]

# Characters that PaddleOCR may produce for a handwritten checkmark or cross.
# 十字记录法 uses × / + / 十 in addition to the more common √ tick.
CHECKMARK_RE = re.compile(r'^[√✓✔vVγ×xX+十]+$')


def resize_image(image_path, max_side=MAX_IMAGE_SIDE):
    """Scale image down so the long side <= max_side."""
    try:
        from PIL import Image
    except ImportError:
        return image_path, False

    try:
        img = Image.open(image_path)
        long_side = max(img.size)
        if long_side <= max_side:
            return image_path, False

        ratio = max_side / long_side
        new_size = (int(img.size[0] * ratio), int(img.size[1] * ratio))
        img = img.resize(new_size, Image.LANCZOS)

        out_path = image_path + '.resized.jpg'
        img.save(out_path, 'JPEG', quality=80)
        return out_path, True
    except Exception as e:
        print(f"Warning: Image resize failed: {e}", file=sys.stderr)
        return image_path, False


def box_x_center(box):
    xs = [p[0] for p in box]
    return (min(xs) + max(xs)) / 2.0


def box_y_center(box):
    ys = [p[1] for p in box]
    return (min(ys) + max(ys)) / 2.0


def box_height(box):
    ys = [p[1] for p in box]
    return abs(max(ys) - min(ys))


def group_rows(items, y_threshold_ratio=0.6):
    """
    Group (box, text, score) items into visual rows by y proximity.
    Each returned row is sorted left-to-right by x.
    """
    if not items:
        return []

    heights = [box_height(b) for b, _, _ in items if box_height(b) > 0]
    avg_h = sum(heights) / len(heights) if heights else 20
    threshold = avg_h * y_threshold_ratio

    sorted_items = sorted(items, key=lambda it: box_y_center(it[0]))

    rows = []
    cur = [sorted_items[0]]
    cur_y = box_y_center(sorted_items[0][0])

    for it in sorted_items[1:]:
        iy = box_y_center(it[0])
        if abs(iy - cur_y) <= threshold:
            cur.append(it)
            # running average keeps drift small on slightly skewed photos
            cur_y = (cur_y * (len(cur) - 1) + iy) / len(cur)
        else:
            rows.append(cur)
            cur = [it]
            cur_y = iy
    rows.append(cur)

    for r in rows:
        r.sort(key=lambda it: box_x_center(it[0]))
    return rows


def match_header_field(text):
    """Return canonical field key if the text matches a header keyword."""
    t = text.strip()
    for field, keywords in HEADER_FIELDS:
        for kw in keywords:
            if kw in t:
                return field
    return None


def detect_header(rows):
    """
    Pick the row that matches the most distinct header fields.
    Returns (row_index, [(field, x_center), ...] sorted by x) or (None, []).
    """
    best_idx = None
    best_cols = []
    for idx, row in enumerate(rows):
        cols = []
        seen = set()
        for box, text, _ in row:
            field = match_header_field(text)
            if field and field not in seen:
                seen.add(field)
                cols.append((field, box_x_center(box)))
        if len(cols) > len(best_cols):
            best_cols = cols
            best_idx = idx
    # Need at least 3 recognizable header columns to trust it as a table.
    if best_idx is None or len(best_cols) < 3:
        return None, []
    best_cols.sort(key=lambda c: c[1])
    return best_idx, best_cols


def build_column_bounds(header_cols):
    """
    From [(field, x_center), ...] (sorted by x) build assignment ranges.
    Boundary between two columns is the midpoint of their centers.
    """
    bounds = []
    n = len(header_cols)
    for i, (field, xc) in enumerate(header_cols):
        left = float('-inf') if i == 0 else (header_cols[i - 1][1] + xc) / 2.0
        right = float('inf') if i == n - 1 else (header_cols[i + 1][1] + xc) / 2.0
        bounds.append((field, left, right))
    return bounds


def assign_to_column(x, bounds):
    for field, left, right in bounds:
        if left <= x < right:
            return field
    return None


def clean_amount(text):
    """Pull the first number out of e.g. '收200', '应收1,200', '￥350'."""
    t = text.replace(',', '').replace('，', '')
    m = re.search(r'\d+(?:\.\d+)?', t)
    return m.group(0) if m else text.strip()


def is_checkmark(text):
    """Return True if the OCR text looks like a handwritten checkmark or cross mark.

    Covers both the common tick (√) and the 十字记录法 family (× + 十 x X).
    """
    t = text.strip()
    return bool(CHECKMARK_RE.match(t)) or t in {
        '√', '✓', '✔', 'V', 'v',   # tick variants
        '×', 'x', 'X', '+', '十',   # cross / plus variants
    }


def normalize_value(field, value):
    value = value.strip()
    if not value:
        return value
    if field == 'amount':
        return clean_amount(value)
    if field == 'gender':
        gm = {'男': '男', '女': '女', 'M': '男', 'F': '女'}
        return gm.get(value, value)
    if field == 'log_date':
        # "3.24" / "3/24" / "3-24" -> keep raw; PHP normalizes against year.
        return value
    if field == 'visit_initial':
        # Split-column: checkmark in the 初诊 column → initial visit.
        return 'initial' if is_checkmark(value) else ''
    if field == 'visit_revisit':
        # Split-column: checkmark in the 复诊 column → revisit.
        return 'revisit' if is_checkmark(value) else ''
    if field == 'visit_type':
        # Merged header (初/复诊): try to read text content first.
        if '初' in value and '复' not in value:
            return 'initial'
        if '复' in value and '初' not in value:
            return 'revisit'
        # Bare checkmark with no positional info → leave empty for manual fix.
        if is_checkmark(value):
            return ''
        return value
    return value


def main():
    if len(sys.argv) < 2:
        print("Usage: python3 worklog_ocr.py <image_path>", file=sys.stderr)
        sys.exit(1)

    image_path = sys.argv[1]
    if not os.path.isfile(image_path):
        print(f"Error: File not found: {image_path}", file=sys.stderr)
        sys.exit(1)

    try:
        from paddleocr import PaddleOCR
    except ImportError:
        print("Error: PaddleOCR not installed. Run: pip3 install paddleocr", file=sys.stderr)
        sys.exit(1)

    work_path, cleanup = resize_image(image_path)

    try:
        ocr = PaddleOCR(
            use_textline_orientation=False,
            lang='ch',
            ocr_version='PP-OCRv4',
        )
        result = ocr.predict(work_path)
    except Exception as e:
        print(f"Error: OCR processing failed: {e}", file=sys.stderr)
        sys.exit(1)
    finally:
        if cleanup and os.path.exists(work_path):
            os.remove(work_path)

    items = []
    if result and len(result) > 0:
        ocr_result = result[0]
        rec_texts = ocr_result.get("rec_texts") or []
        rec_scores = ocr_result.get("rec_scores") or []
        dt_polys = ocr_result.get("dt_polys") or []
        for b, t, s in zip(dt_polys, rec_texts, rec_scores):
            if t and t.strip() and float(s) > 0.15:
                box = [[float(p[0]), float(p[1])] for p in b]
                items.append((box, t.strip(), float(s)))

    rows = group_rows(items)
    raw_lines = [" ".join(t for _, t, _ in r) for r in rows]

    confs = [s for _, _, s in items]
    avg_confidence = round(sum(confs) / len(confs), 4) if confs else 0.0

    header_idx, header_cols = detect_header(rows)

    if header_idx is None:
        # No reliable header — return raw lines so the UI can fall back
        # to manual entry instead of emitting garbage columns.
        print(json.dumps({
            "header_found": False,
            "columns": [],
            "rows": [],
            "avg_confidence": avg_confidence,
            "raw_lines": raw_lines,
        }, ensure_ascii=False))
        return

    bounds = build_column_bounds(header_cols)
    internal_columns = [field for field, _, _ in bounds]

    out_rows = []
    for idx in range(header_idx + 1, len(rows)):
        row = rows[idx]
        bucket = {field: [] for field in internal_columns}
        for box, text, _ in row:
            field = assign_to_column(box_x_center(box), bounds)
            if field is not None:
                bucket[field].append(text)

        record = {}
        for field in internal_columns:
            joined = " ".join(bucket[field]).strip()
            record[field] = normalize_value(field, joined)

        # Merge split visit sub-columns into a single visit_type field.
        # visit_initial / visit_revisit hold 'initial', 'revisit', or '' after
        # normalize_value; whichever is non-empty wins.
        vi = record.pop('visit_initial', None)
        vr = record.pop('visit_revisit', None)
        if vi is not None or vr is not None:
            if not record.get('visit_type'):
                record['visit_type'] = vi or vr or ''

        # Skip empty rows (no name and no diagnosis and no amount)
        if not any([record.get('patient_name'),
                    record.get('diagnosis'),
                    record.get('amount')]):
            continue
        out_rows.append(record)

    # Build the public column list: replace internal sub-columns with visit_type.
    seen_visit = False
    columns = []
    for c in internal_columns:
        if c in ('visit_initial', 'visit_revisit'):
            if not seen_visit:
                columns.append('visit_type')
                seen_visit = True
        else:
            columns.append(c)

    print(json.dumps({
        "header_found": True,
        "columns": columns,
        "rows": out_rows,
        "avg_confidence": avg_confidence,
        "raw_lines": raw_lines,
    }, ensure_ascii=False))


if __name__ == "__main__":
    main()
