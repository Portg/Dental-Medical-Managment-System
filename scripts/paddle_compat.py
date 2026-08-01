#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
PaddleOCR 2.x / 3.x 兼容层。

背景：Windows 7 版本必须运行在 Python 3.8 上（3.9 起最低要求 Win8.1），
而 PaddleOCR 3.x 的依赖树（anyio、httpx、huggingface_hub 等）已全部要求
Python >= 3.9，因此 Win7 目标机只能使用 PaddleOCR 2.x。

两个大版本的接口差异：

                 PaddleOCR 2.x                      PaddleOCR 3.x
  构造参数        use_angle_cls=                     use_textline_orientation=
  识别方法        ocr.ocr(path, cls=False)           ocr.predict(path)
  返回结构        [[[box, (text, score)], ...]]      [{rec_texts, rec_scores, dt_polys}]

本模块把两者统一成 3.x 的字典结构，因此下游的表格重建、行分组、纠错逻辑
完全不需要改动。
"""

__all__ = ['build_ocr', 'run_ocr', 'paddleocr_major']


def paddleocr_major():
    """返回已安装 PaddleOCR 的主版本号（取不到时按 3 处理）。"""
    try:
        import paddleocr
    except ImportError:
        return None

    raw = getattr(paddleocr, '__version__', '') or ''
    head = raw.split('.')[0].strip()
    try:
        return int(head)
    except ValueError:
        return 3


def build_ocr(lang='ch', ocr_version='PP-OCRv4', use_orientation=False):
    """构造 PaddleOCR 实例，自动适配 2.x / 3.x 的构造参数。"""
    from paddleocr import PaddleOCR

    if paddleocr_major() == 2:
        # 2.x：show_log 关闭刷屏日志；该参数在 3.x 已移除。
        return PaddleOCR(
            use_angle_cls=use_orientation,
            lang=lang,
            ocr_version=ocr_version,
            show_log=False,
        )

    return PaddleOCR(
        use_textline_orientation=use_orientation,
        lang=lang,
        ocr_version=ocr_version,
    )


def _normalize_v2(raw):
    """把 2.x 的 [[[box, (text, score)], ...]] 转成 3.x 的字典列表。"""
    pages = []
    for page in (raw or []):
        rec_texts, rec_scores, dt_polys = [], [], []
        # 2.x 在整页无文字时会返回 [None]
        for line in (page or []):
            if not line or len(line) < 2:
                continue
            box, payload = line[0], line[1]
            if isinstance(payload, (list, tuple)) and len(payload) >= 2:
                text, score = payload[0], payload[1]
            else:
                text, score = payload, 1.0
            dt_polys.append(box)
            rec_texts.append(text)
            rec_scores.append(score)
        pages.append({
            'rec_texts': rec_texts,
            'rec_scores': rec_scores,
            'dt_polys': dt_polys,
        })
    return pages


def run_ocr(ocr, image_path):
    """执行识别，统一返回 3.x 结构：[{rec_texts, rec_scores, dt_polys}, ...]"""
    if paddleocr_major() == 2:
        return _normalize_v2(ocr.ocr(image_path, cls=False))

    return ocr.predict(image_path)
