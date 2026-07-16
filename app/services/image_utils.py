"""Small, dependency-light helpers around Pillow.

Kept deliberately generic so both the Track B compositor and the Track A
providers can share the same primitives.
"""
from __future__ import annotations

from functools import lru_cache
from io import BytesIO

from PIL import Image, ImageDraw, ImageFont

WHITE = (255, 255, 255)
INK = (33, 37, 41)
MUTED = (108, 117, 125)
ACCENT = (176, 141, 87)  # muted gold — fits the jewelry domain
PANEL = (248, 249, 250)
BORDER = (222, 226, 230)


@lru_cache(maxsize=32)
def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    """Return a scalable font, falling back to Pillow's bundled DejaVuSans.

    Pillow >= 10.1 ships a TrueType default that ``load_default(size=...)``
    scales, so this works with no system fonts installed.
    """
    candidates = (
        ["DejaVuSans-Bold.ttf", "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"]
        if bold
        else ["DejaVuSans.ttf", "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"]
    )
    for path in candidates:
        try:
            return ImageFont.truetype(path, size)
        except OSError:
            continue
    try:
        return ImageFont.load_default(size=size)
    except TypeError:  # very old Pillow
        return ImageFont.load_default()


def load_image(data: bytes) -> Image.Image:
    """Decode uploaded bytes into a normalised RGB image."""
    img = Image.open(BytesIO(data))
    return img.convert("RGB")


def fit_within(img: Image.Image, box_w: int, box_h: int, pad: int = 0,
               background=WHITE) -> Image.Image:
    """Return a ``box_w`` x ``box_h`` canvas with ``img`` centred and scaled
    to fit (never upscaled beyond the box), padded with ``background``."""
    inner_w, inner_h = box_w - 2 * pad, box_h - 2 * pad
    src = img.copy()
    src.thumbnail((inner_w, inner_h), Image.LANCZOS)
    canvas = Image.new("RGB", (box_w, box_h), background)
    off = ((box_w - src.width) // 2, (box_h - src.height) // 2)
    if src.mode == "RGBA":
        canvas.paste(src, off, src)
    else:
        canvas.paste(src, off)
    return canvas


def text_size(draw: ImageDraw.ImageDraw, text: str,
              font: ImageFont.ImageFont) -> tuple[int, int]:
    left, top, right, bottom = draw.textbbox((0, 0), text, font=font)
    return right - left, bottom - top


def draw_centered(draw: ImageDraw.ImageDraw, text: str, cx: int, cy: int,
                  font: ImageFont.ImageFont, fill=INK) -> None:
    w, h = text_size(draw, text, font)
    draw.text((cx - w / 2, cy - h / 2), text, font=font, fill=fill)


def rounded_panel(size: tuple[int, int], radius: int = 14, fill=PANEL,
                  outline=BORDER, width: int = 1) -> Image.Image:
    img = Image.new("RGB", size, WHITE)
    draw = ImageDraw.Draw(img)
    draw.rounded_rectangle(
        [0, 0, size[0] - 1, size[1] - 1], radius=radius,
        fill=fill, outline=outline, width=width,
    )
    return img


def to_png_bytes(img: Image.Image) -> bytes:
    buf = BytesIO()
    img.save(buf, format="PNG")
    return buf.getvalue()
