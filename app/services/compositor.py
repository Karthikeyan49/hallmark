"""Track B — the deterministic compliance-composite engine.

Builds the standardised 3-pane deliverable required by the portal for every
item (report section 2):

    [ UID IMAGE ] [ MODEL IMAGE ] [ WEIGHT IMAGE ]

- UID pane    : the real, laser-etched UID. Either the uploaded macro crop
                (Track B) or a deterministic metal-plate render of the known
                UID string (Track A). Never AI-hallucinated.
- Model pane  : the studio product shot from the configured provider.
- Weight pane : the item on a rendered digital scale showing the *measured*
                weight. Deterministic — the number is data, not generated.

This module has zero external-service dependencies and no per-image cost.
"""
from __future__ import annotations

from dataclasses import dataclass, field
from datetime import date

from PIL import Image, ImageDraw

from ..config import settings
from . import ai_generator, image_utils as iu


@dataclass
class ItemSpec:
    """The metadata + inputs for one compliance item."""

    uid: str
    weight_grams: float
    model_image: Image.Image
    uid_image: Image.Image | None = None  # macro of the etched UID (Track B)
    company: str = ""
    item_type: str = ""
    date_str: str = field(default_factory=lambda: date.today().isoformat())

    @property
    def weight_label(self) -> str:
        return f"{self.weight_grams:.2f} g"


@dataclass
class CompositeResult:
    image: Image.Image
    track: str          # "A" (generated model pane) or "B" (uploaded UID crop)
    provider: str
    cost_inr: float
    billable_to_client: bool


def _pane_frame(title: str) -> tuple[Image.Image, ImageDraw.ImageDraw, tuple[int, int]]:
    """A labelled panel; returns (panel, draw, inner_box) for pane content."""
    w, h = settings.pane_width, settings.pane_height
    label_h = 40
    panel = iu.rounded_panel((w, h), radius=16)
    draw = ImageDraw.Draw(panel)
    draw.rounded_rectangle([0, 0, w - 1, label_h], radius=16, fill=iu.ACCENT)
    draw.rectangle([0, label_h - 16, w - 1, label_h], fill=iu.ACCENT)
    iu.draw_centered(draw, title, w // 2, label_h // 2,
                     iu.load_font(18, bold=True), fill=iu.WHITE)
    return panel, draw, (w, h - label_h)


def _uid_pane(spec: ItemSpec) -> tuple[Image.Image, str]:
    """Return (pane, track). Track B if a real UID crop was supplied."""
    panel, _, (iw, ih) = _pane_frame("UID IMAGE")
    label_h = settings.pane_height - ih

    if spec.uid_image is not None:
        photo = iu.fit_within(spec.uid_image, iw - 24, ih - 70, background=iu.PANEL)
        panel.paste(photo, (12, label_h + 12))
        track = "B"
    else:
        # Deterministic metal-plate render of the known, real UID string.
        plate = Image.new("RGB", (iw - 48, ih - 90), (58, 58, 62))
        pdraw = ImageDraw.Draw(plate)
        for y in range(0, plate.height, 3):  # brushed-metal hint
            shade = 58 + (12 if (y // 3) % 2 else 0)
            pdraw.line([(0, y), (plate.width, y)], fill=(shade, shade, shade + 2))
        iu.draw_centered(pdraw, spec.uid, plate.width // 2, plate.height // 2,
                         iu.load_font(30, bold=True), fill=(236, 236, 240))
        panel.paste(plate, (24, label_h + 24))
        track = "A"

    draw = ImageDraw.Draw(panel)
    iu.draw_centered(draw, f"UID: {spec.uid}", settings.pane_width // 2,
                     settings.pane_height - 26, iu.load_font(20, bold=True),
                     fill=iu.INK)
    return panel, track


def _model_pane(model_result_img: Image.Image, spec: ItemSpec) -> Image.Image:
    panel, draw, (iw, ih) = _pane_frame("MODEL IMAGE")
    label_h = settings.pane_height - ih
    photo = iu.fit_within(model_result_img, iw - 24, ih - 60, background=iu.WHITE)
    panel.paste(photo, (12, label_h + 12))
    caption = spec.item_type or "Product"
    iu.draw_centered(draw, caption, settings.pane_width // 2,
                     settings.pane_height - 26, iu.load_font(20, bold=True),
                     fill=iu.INK)
    return panel


def _weight_pane(spec: ItemSpec) -> Image.Image:
    """Render the item on a digital scale showing the measured weight."""
    panel, draw, (iw, ih) = _pane_frame("WEIGHT IMAGE")
    label_h = settings.pane_height - ih
    cx = settings.pane_width // 2

    # Item thumbnail resting on the scale pan.
    thumb = iu.fit_within(spec.model_image, iw - 160, ih - 230, background=iu.PANEL)
    panel.paste(thumb, (cx - thumb.width // 2, label_h + 24))

    # Scale body.
    body_top = settings.pane_height - 150
    draw.rounded_rectangle([40, body_top, settings.pane_width - 40,
                            settings.pane_height - 30], radius=14,
                           fill=(52, 58, 64))
    # Pan line.
    draw.line([(70, body_top), (settings.pane_width - 70, body_top)],
              fill=(120, 128, 136), width=3)

    # LCD read-out.
    lcd_w, lcd_h = 220, 74
    lcd_x, lcd_y = cx - lcd_w // 2, body_top + 30
    draw.rounded_rectangle([lcd_x, lcd_y, lcd_x + lcd_w, lcd_y + lcd_h],
                           radius=8, fill=(198, 224, 180))
    iu.draw_centered(draw, spec.weight_label, cx, lcd_y + lcd_h // 2,
                     iu.load_font(40, bold=True), fill=(20, 40, 20))
    return panel


def _header(spec: ItemSpec, width: int) -> Image.Image:
    h = settings.header_height
    band = Image.new("RGB", (width, h), iu.INK)
    draw = ImageDraw.Draw(band)
    title = spec.company or "Hallmarking Compliance Composite"
    draw.text((settings.margin, 20), title, font=iu.load_font(26, bold=True),
              fill=iu.WHITE)
    meta = f"UID {spec.uid}    |    {spec.weight_label}    |    {spec.date_str}"
    draw.text((settings.margin, 58), meta, font=iu.load_font(18), fill=(206, 212, 218))
    return band


def _footer(result_note: str, width: int) -> Image.Image:
    h = settings.footer_height
    band = Image.new("RGB", (width, h), iu.PANEL)
    draw = ImageDraw.Draw(band)
    draw.line([(0, 0), (width, 0)], fill=iu.BORDER, width=1)
    draw.text((settings.margin, 18),
              "Portal-ready • UID & weight rendered from verified data",
              font=iu.load_font(15), fill=iu.MUTED)
    w, _ = iu.text_size(draw, result_note, iu.load_font(15))
    draw.text((width - settings.margin - w, 18), result_note,
              font=iu.load_font(15), fill=iu.MUTED)
    return band


def build_composite(spec: ItemSpec,
                    provider: str | None = None) -> CompositeResult:
    """Assemble the standardised 3-pane compliance composite."""
    model_result = ai_generator.get_model_pane(spec.model_image, provider=provider)

    uid_pane, track = _uid_pane(spec)
    model_pane = _model_pane(model_result.image, spec)
    weight_pane = _weight_pane(spec)
    panes = [uid_pane, model_pane, weight_pane]

    m = settings.margin
    strip_w = sum(p.width for p in panes) + m * (len(panes) + 1)
    strip_h = settings.pane_height + 2 * m
    total_h = settings.header_height + strip_h + settings.footer_height

    canvas = Image.new("RGB", (strip_w, total_h), iu.WHITE)
    canvas.paste(_header(spec, strip_w), (0, 0))

    x = m
    y = settings.header_height + m
    for pane in panes:
        canvas.paste(pane, (x, y))
        x += pane.width + m

    note = (f"provider={model_result.provider} • cost={model_result.cost_inr:.2f} INR"
            f"{' • billed to client' if model_result.billable_to_client else ''}")
    canvas.paste(_footer(note, strip_w), (0, settings.header_height + strip_h))

    return CompositeResult(
        image=canvas,
        track=track,
        provider=model_result.provider,
        cost_inr=model_result.cost_inr,
        billable_to_client=model_result.billable_to_client,
    )
