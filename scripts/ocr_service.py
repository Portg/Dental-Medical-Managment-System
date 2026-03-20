#!/usr/bin/env python3
"""
PaddleOCR wrapper for dental clinic medical record recognition.

Usage:
    python3 ocr_service.py <image_path>

Output (stdout):
    JSON: { "raw_text": str, "lines": [{"text": str, "confidence": float, "bbox": list}], "avg_confidence": float }

Errors go to stderr, never pollute stdout JSON.

Requirements:
    pip3 install paddleocr Pillow
"""

import sys
import json
import os
import re

# Suppress PaddleX connectivity check (avoids slow network probe on every run)
os.environ.setdefault('PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK', 'True')

# Max image dimension (long side). Larger images are scaled down before OCR.
# 960px matches PaddleOCR's internal det_limit_side_len, avoiding redundant
# double-resize while retaining full accuracy for printed/handwritten A4 forms.
MAX_IMAGE_SIDE = 960

# ── External config: scripts/ocr_corrections.json ─────────────────────
# Labels and corrections are loaded from a JSON file so clinic staff can
# add new entries without touching Python code.
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(SCRIPT_DIR, 'ocr_corrections.json')


def _load_config():
    """Load labels and corrections from external JSON; fall back to empty defaults."""
    if os.path.isfile(CONFIG_PATH):
        try:
            with open(CONFIG_PATH, 'r', encoding='utf-8') as f:
                cfg = json.load(f)
            labels = cfg.get('labels', [])
            # Sort labels longest-first for greedy matching
            labels.sort(key=lambda s: -len(s))
            # Sort corrections longest-key-first to avoid partial replacements
            raw = cfg.get('corrections', {})
            corrections = sorted(raw.items(), key=lambda kv: -len(kv[0]))
            return labels, corrections
        except Exception as e:
            print(f"Warning: Failed to load {CONFIG_PATH}: {e}", file=sys.stderr)
    return [], []


KNOWN_LABELS, DENTAL_CORRECTIONS = _load_config()


def resize_image(image_path, max_side=MAX_IMAGE_SIDE):
    """
    Scale image down so the long side ≤ max_side.
    Returns (output_path, needs_cleanup). Skips if already small enough.
    """
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


def merge_into_rows(items, y_threshold_ratio=0.5):
    """
    Group OCR text items into logical rows by y-coordinate proximity,
    then sort left-to-right within each row.

    This fixes the core issue: table-format forms where labels and values
    in the same row (e.g. "姓名" and "曹铭熙") get split into separate
    lines and may even appear in wrong order when sorted only by y.
    """
    if not items:
        return []

    # Dynamic threshold based on average text height
    heights = [abs(b[3][1] - b[0][1]) for b, _, _ in items
               if abs(b[3][1] - b[0][1]) > 0]
    avg_h = sum(heights) / len(heights) if heights else 20
    threshold = avg_h * y_threshold_ratio

    def y_center(item):
        b = item[0]
        return (b[0][1] + b[2][1]) / 2

    sorted_items = sorted(items, key=y_center)

    rows = []
    cur_row = [sorted_items[0]]
    cur_y = y_center(sorted_items[0])

    for item in sorted_items[1:]:
        iy = y_center(item)
        if abs(iy - cur_y) <= threshold:
            cur_row.append(item)
        else:
            rows.append(cur_row)
            cur_row = [item]
            cur_y = iy
    rows.append(cur_row)

    # Sort within each row by x-coordinate (left to right)
    for row in rows:
        row.sort(key=lambda it: it[0][0][0])

    return rows


def apply_dental_corrections(text):
    """
    Post-OCR correction using the external corrections dictionary
    (scripts/ocr_corrections.json). Entries are applied longest-first
    to avoid partial replacements.
    """
    for wrong, correct in DENTAL_CORRECTIONS:
        text = text.replace(wrong, correct)
    return text


def fix_digit_letter_confusion(text):
    """
    Fix common OCR l/1 and O/0 confusion in digit contexts.
    e.g. "l0岁" → "10岁", "lo岁" → "10岁", "l5233" → "15233"
    """
    # "lo" / "lO" / "l0" before 岁 or digit → "10" (covers age like "lo岁")
    text = re.sub(r'l[oO0](?=\d|岁)', '10', text)
    # lowercase L before digit → 1 (e.g. "l5233" → "15233")
    text = re.sub(r'l(\d)', r'1\1', text)
    # uppercase O or lowercase o between digits → 0 (e.g. "2O25" → "2025")
    text = re.sub(r'(\d)[oO](\d)', r'\g<1>0\2', text)
    return text


def insert_label_colons(text):
    """
    Insert '：' after known medical form labels if not already present.

    e.g. "姓名 曹铭熙 性别 女" → "姓名：曹铭熙 性别：女"

    Uses negative lookahead to avoid:
    - double-inserting when colon already exists (followed by ：or :)
    - breaking longer labels (e.g. "治疗" inside "治疗意见" — blocked
      because "治疗" is followed by Chinese char "意")
    """
    for label in KNOWN_LABELS:
        pattern = re.compile(
            re.escape(label) + r'(?![：:\u4e00-\u9fa5])\s*',
            re.UNICODE
        )
        text = pattern.sub(label + '：', text)
    return text


def main():
    if len(sys.argv) < 2:
        print("Usage: python3 ocr_service.py <image_path>", file=sys.stderr)
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

    # ── Resize large images to speed up detection ──────────────────────
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

    lines = []
    total_confidence = 0.0

    # PaddleOCR 3.x: result is a list of OCRResult objects (one per image).
    # Each has dict keys: rec_texts, rec_scores, dt_polys.
    if result and len(result) > 0:
        ocr_result = result[0]
        rec_texts = ocr_result.get("rec_texts") or []
        rec_scores = ocr_result.get("rec_scores") or []
        dt_polys = ocr_result.get("dt_polys") or []

        items = list(zip(dt_polys, rec_texts, rec_scores))

        # Filter out empty text and very low confidence items (noise)
        items = [(b, t, float(s)) for b, t, s in items
                 if t.strip() and float(s) > 0.15]

        # ── Core fix: merge text boxes into logical rows ──────────
        rows = merge_into_rows(items)

        for row in rows:
            # Join items in the same row with spaces (left to right)
            merged_text = " ".join(t for _, t, _ in row)

            # Insert colons after known labels so the PHP parser can
            # match "关键词：值" patterns
            merged_text = insert_label_colons(merged_text)

            confs = [c for _, _, c in row]
            avg_c = sum(confs) / len(confs)

            # Combined bounding box encompassing the entire row
            all_x = [p[0] for b, _, _ in row for p in b]
            all_y = [p[1] for b, _, _ in row for p in b]
            row_bbox = [
                [int(min(all_x)), int(min(all_y))],
                [int(max(all_x)), int(min(all_y))],
                [int(max(all_x)), int(max(all_y))],
                [int(min(all_x)), int(max(all_y))],
            ]

            lines.append({
                "text": merged_text,
                "confidence": round(avg_c, 4),
                "bbox": row_bbox,
            })
            total_confidence += avg_c

    avg_confidence = round(total_confidence / len(lines), 4) if lines else 0.0
    raw_text = "\n".join(line["text"] for line in lines)

    # Post-processing: fix digit/letter confusion, then dental corrections
    raw_text = fix_digit_letter_confusion(raw_text)
    raw_text = apply_dental_corrections(raw_text)

    output = {
        "raw_text": raw_text,
        "lines": lines,
        "avg_confidence": avg_confidence,
    }

    print(json.dumps(output, ensure_ascii=False))


if __name__ == "__main__":
    main()
