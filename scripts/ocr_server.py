#!/usr/bin/env python3
"""
OCR persistent HTTP server — loads PaddleOCR model ONCE, serves requests continuously.

Usage:
    python3 ocr_server.py                  # default: 127.0.0.1:5000
    python3 ocr_server.py --port 5001      # custom port

Endpoints:
    POST /recognize   multipart file field "image" → JSON result
    GET  /health      → {"status": "ok"}

The model is loaded at startup (~4s), then each request only costs inference time (~10-15s).
"""

import argparse
import json
import os
import re
import sys
import tempfile
import time

os.environ.setdefault('PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK', 'True')

# ── Reuse all logic from ocr_service.py ──────────────────────────────
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, SCRIPT_DIR)

from ocr_service import (
    resize_image,
    merge_into_rows,
    insert_label_colons,
    apply_dental_corrections,
    fix_digit_letter_confusion,
)


def build_raw_text(ocr_result):
    """Process PaddleOCR result into merged, corrected raw_text + lines."""
    lines = []
    total_confidence = 0.0

    if ocr_result and len(ocr_result) > 0:
        first = ocr_result[0]
        rec_texts = first.get("rec_texts") or []
        rec_scores = first.get("rec_scores") or []
        dt_polys = first.get("dt_polys") or []

        items = [(b, t, float(s)) for b, t, s in zip(dt_polys, rec_texts, rec_scores)
                 if t.strip() and float(s) > 0.15]

        rows = merge_into_rows(items)

        for row in rows:
            merged_text = " ".join(t for _, t, _ in row)
            merged_text = insert_label_colons(merged_text)

            confs = [c for _, _, c in row]
            avg_c = sum(confs) / len(confs)

            all_x = [p[0] for b, _, _ in row for p in b]
            all_y = [p[1] for b, _, _ in row for p in b]
            row_bbox = [
                [int(min(all_x)), int(min(all_y))],
                [int(max(all_x)), int(min(all_y))],
                [int(max(all_x)), int(max(all_y))],
                [int(min(all_x)), int(max(all_y))],
            ]

            lines.append({"text": merged_text, "confidence": round(avg_c, 4), "bbox": row_bbox})
            total_confidence += avg_c

    avg_confidence = round(total_confidence / len(lines), 4) if lines else 0.0
    raw_text = "\n".join(line["text"] for line in lines)
    raw_text = fix_digit_letter_confusion(raw_text)
    raw_text = apply_dental_corrections(raw_text)

    return {"raw_text": raw_text, "lines": lines, "avg_confidence": avg_confidence}


def main():
    parser = argparse.ArgumentParser(description='OCR persistent HTTP server')
    parser.add_argument('--host', default='127.0.0.1')
    parser.add_argument('--port', type=int, default=5000)
    args = parser.parse_args()

    # ── Load model ONCE at startup ────────────────────────────────────
    print("[OCR Server] Loading PaddleOCR model...", flush=True)
    from paddleocr import PaddleOCR
    ocr = PaddleOCR(use_textline_orientation=False, lang='ch', ocr_version='PP-OCRv4')
    print("[OCR Server] Model loaded. Starting server...", flush=True)

    # ── Flask app ─────────────────────────────────────────────────────
    from flask import Flask, request, jsonify
    app = Flask(__name__)

    @app.route('/health', methods=['GET'])
    def health():
        return jsonify({"status": "ok"})

    @app.route('/recognize', methods=['POST'])
    def recognize():
        if 'image' not in request.files:
            return jsonify({"error": "No 'image' file in request"}), 400

        file = request.files['image']
        if not file.filename:
            return jsonify({"error": "Empty filename"}), 400

        # Save to temp file
        suffix = os.path.splitext(file.filename)[1] or '.jpg'
        tmp = tempfile.NamedTemporaryFile(suffix=suffix, delete=False)
        try:
            t0 = time.time()
            file.save(tmp.name)

            # Resize for speed
            work_path, cleanup = resize_image(tmp.name)
            t1 = time.time()

            try:
                result = ocr.predict(work_path)
            finally:
                if cleanup and os.path.exists(work_path):
                    os.remove(work_path)
            t2 = time.time()

            output = build_raw_text(result)
            t3 = time.time()

            print(f"[OCR] resize={t1-t0:.2f}s  inference={t2-t1:.2f}s  post={t3-t2:.2f}s  total={t3-t0:.2f}s", flush=True)
            return jsonify(output)

        except Exception as e:
            return jsonify({"error": str(e)}), 500

        finally:
            if os.path.exists(tmp.name):
                os.remove(tmp.name)

    print(f"[OCR Server] Listening on http://{args.host}:{args.port}", flush=True)
    app.run(host=args.host, port=args.port, threaded=False)


if __name__ == "__main__":
    main()
