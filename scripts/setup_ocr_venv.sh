#!/usr/bin/env bash
# ============================================================
# OCR Python Virtual Environment Setup (Mac / Linux)
# Creates scripts/venv with PaddleOCR + Flask dependencies.
#
# Usage:
#   bash scripts/setup_ocr_venv.sh           # install only
#   bash scripts/setup_ocr_venv.sh --start   # install + start server
# ============================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
VENV_DIR="$SCRIPT_DIR/venv"
REQ_FILE="$SCRIPT_DIR/requirements.txt"
START_SERVER=false

if [[ "${1:-}" == "--start" ]]; then
    START_SERVER=true
fi

# ── 1. Find Python 3.9-3.12 (PaddlePaddle supported range) ──────────
PYTHON=""
for ver in python3.11 python3.10 python3.12 python3.9 python3; do
    if command -v "$ver" &>/dev/null; then
        PYTHON="$ver"
        break
    fi
done

if [[ -z "$PYTHON" ]]; then
    echo "ERROR: Python 3.9+ not found. Please install Python first." >&2
    exit 1
fi

PY_VER=$("$PYTHON" -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}')")
echo "[INFO] Using Python $PY_VER ($PYTHON)"

# ── 2. Create virtual environment ────────────────────────────────────
if [ ! -d "$VENV_DIR" ]; then
    echo "[INFO] Creating virtual environment at $VENV_DIR ..."
    "$PYTHON" -m venv "$VENV_DIR"
else
    echo "[INFO] Virtual environment already exists at $VENV_DIR"
fi

# ── 3. Install / upgrade dependencies ────────────────────────────────
echo "[INFO] Installing dependencies ..."
"$VENV_DIR/bin/pip" install --upgrade pip -q
"$VENV_DIR/bin/pip" install -r "$REQ_FILE"

# ── 4. Verify installation ───────────────────────────────────────────
echo ""
echo "[INFO] Verifying installation ..."
"$VENV_DIR/bin/python3" -c "from paddleocr import PaddleOCR; print('  PaddleOCR OK')" 2>/dev/null
"$VENV_DIR/bin/python3" -c "from flask import Flask; print('  Flask OK')" 2>/dev/null
"$VENV_DIR/bin/python3" -c "from PIL import Image; print('  Pillow OK')" 2>/dev/null

# ── 5. Print .env config ─────────────────────────────────────────────
echo ""
echo "============================================================"
echo "[OK] OCR environment ready."
echo ""
echo "  Add to .env:"
echo "    OCR_PYTHON_PATH=$VENV_DIR/bin/python3"
echo "    OCR_TIMEOUT=120"
echo "    OCR_SERVER_URL=http://127.0.0.1:5000"
echo ""
echo "  Start persistent OCR server (recommended):"
echo "    $VENV_DIR/bin/python3 $SCRIPT_DIR/ocr_server.py"
echo ""
echo "  Or run in background:"
echo "    nohup $VENV_DIR/bin/python3 $SCRIPT_DIR/ocr_server.py > $SCRIPT_DIR/ocr_server.log 2>&1 &"
echo "============================================================"

# ── 6. Optionally start server ────────────────────────────────────────
if $START_SERVER; then
    echo ""
    echo "[INFO] Starting OCR server ..."
    exec "$VENV_DIR/bin/python3" "$SCRIPT_DIR/ocr_server.py"
fi
