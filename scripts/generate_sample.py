"""Generate a demo composite without the HTTP server.

Usage:
    python scripts/generate_sample.py [MODEL_IMAGE] [UID] [WEIGHT_GRAMS]

With no model image it synthesises a placeholder product so the demo runs
anywhere. Output is written to ``samples/demo_composite.png``.
"""
from __future__ import annotations

import os
import sys

from PIL import Image, ImageDraw

# Allow running as `python scripts/generate_sample.py` from the repo root.
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app.services import compositor, image_utils as iu  # noqa: E402


def _placeholder_ring(size=(500, 500)) -> Image.Image:
    img = Image.new("RGB", size, (245, 240, 230))
    d = ImageDraw.Draw(img)
    cx, cy = size[0] // 2, size[1] // 2
    d.ellipse([cx - 150, cy - 150, cx + 150, cy + 150], outline=(176, 141, 87), width=34)
    d.ellipse([cx - 30, cy - 190, cx + 30, cy - 130], fill=(120, 200, 230),
              outline=(90, 160, 190), width=4)
    return img


def main() -> None:
    args = sys.argv[1:]
    model_path = args[0] if len(args) > 0 else None
    uid = args[1] if len(args) > 1 else "HUID-7A2C9K"
    weight = float(args[2]) if len(args) > 2 else 2.00

    model_img = Image.open(model_path).convert("RGB") if model_path else _placeholder_ring()

    spec = compositor.ItemSpec(
        uid=uid, weight_grams=weight, model_image=model_img,
        company="Acme Jewels Pvt Ltd", item_type="Ring",
    )
    result = compositor.build_composite(spec)

    os.makedirs("samples", exist_ok=True)
    out = os.path.join("samples", "demo_composite.png")
    with open(out, "wb") as fh:
        fh.write(iu.to_png_bytes(result.image))
    print(f"Wrote {out}  (track={result.track}, provider={result.provider}, "
          f"cost={result.cost_inr} INR)")


if __name__ == "__main__":
    main()
