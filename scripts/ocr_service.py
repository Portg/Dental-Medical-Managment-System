#!/usr/bin/env python3
"""
PaddleOCR wrapper for dental clinic medical record recognition.

Usage:
    python3 ocr_service.py <image_path>

Output (stdout):
    JSON: { "raw_text": str, "lines": [{"text": str, "confidence": float, "bbox": list}], "avg_confidence": float }

Errors go to stderr, never pollute stdout JSON.

Requirements:
    pip3 install paddlepaddle paddleocr
"""

import sys
import json
import os


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
        print("Error: PaddleOCR not installed. Run: pip3 install paddlepaddle paddleocr", file=sys.stderr)
        sys.exit(1)

    try:
        ocr = PaddleOCR(use_angle_cls=True, lang='ch', show_log=False)
        result = ocr.ocr(image_path, cls=True)
    except Exception as e:
        print(f"Error: OCR processing failed: {e}", file=sys.stderr)
        sys.exit(1)

    lines = []
    total_confidence = 0.0

    if result and result[0]:
        raw_lines = result[0]

        # Sort by y-coordinate (top of bounding box) to maintain reading order
        raw_lines.sort(key=lambda item: item[0][0][1])

        for item in raw_lines:
            bbox = item[0]  # [[x1,y1],[x2,y2],[x3,y3],[x4,y4]]
            text = item[1][0]
            confidence = item[1][1]

            lines.append({
                "text": text,
                "confidence": round(confidence, 4),
                "bbox": [[int(p[0]), int(p[1])] for p in bbox]
            })
            total_confidence += confidence

    avg_confidence = round(total_confidence / len(lines), 4) if lines else 0.0
    raw_text = "\n".join(line["text"] for line in lines)

    output = {
        "raw_text": raw_text,
        "lines": lines,
        "avg_confidence": avg_confidence
    }

    print(json.dumps(output, ensure_ascii=False))


if __name__ == "__main__":
    main()
