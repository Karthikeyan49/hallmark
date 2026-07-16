"""HTTP surface for the compliance-image pipeline."""
from __future__ import annotations

import os
import re
import uuid

from fastapi import APIRouter, File, Form, HTTPException, Query, UploadFile
from fastapi.responses import JSONResponse, Response

from ..config import settings
from ..services import compositor, image_utils as iu

router = APIRouter(prefix="/api", tags=["compliance"])

_UID_RE = re.compile(r"^[A-Za-z0-9\-/]{3,40}$")


def _parse_spec(uid: str, weight_grams: float, company: str, item_type: str,
                model_bytes: bytes, uid_bytes: bytes | None) -> compositor.ItemSpec:
    if not _UID_RE.match(uid or ""):
        raise HTTPException(422, "uid must be 3-40 chars: letters, digits, - / only")
    if weight_grams <= 0:
        raise HTTPException(422, "weight_grams must be greater than 0")
    try:
        model_img = iu.load_image(model_bytes)
    except Exception:
        raise HTTPException(422, "model_image is not a readable image")
    uid_img = None
    if uid_bytes:
        try:
            uid_img = iu.load_image(uid_bytes)
        except Exception:
            raise HTTPException(422, "uid_image is not a readable image")
    return compositor.ItemSpec(
        uid=uid, weight_grams=weight_grams, model_image=model_img,
        uid_image=uid_img, company=company or "", item_type=item_type or "",
    )


@router.post("/composite")
async def create_composite(
    uid: str = Form(..., description="Real laser-etched UID string"),
    weight_grams: float = Form(..., description="Measured weight in grams"),
    company: str = Form(""),
    item_type: str = Form("", description="e.g. Ring, Pendant, Stud"),
    provider: str | None = Query(None, description="Override AI model-pane provider"),
    model_image: UploadFile = File(..., description="Single studio product photo"),
    uid_image: UploadFile | None = File(
        None, description="Optional macro of the etched UID (enables Track B)"),
):
    """Generate the standardised 3-pane compliance composite and return a PNG.

    Track is chosen automatically: supplying ``uid_image`` -> Track B (overlay
    the real UID macro); omitting it -> Track A (deterministic UID render +
    provider-enhanced model pane).
    """
    model_bytes = await model_image.read()
    uid_bytes = await uid_image.read() if uid_image is not None else None
    spec = _parse_spec(uid, weight_grams, company, item_type, model_bytes, uid_bytes)

    result = compositor.build_composite(spec, provider=provider)
    png = iu.to_png_bytes(result.image)

    headers = {
        "X-Compliance-Track": result.track,
        "X-Model-Provider": result.provider,
        "X-Cost-INR": f"{result.cost_inr:.2f}",
        "X-Billed-To-Client": "true" if result.billable_to_client else "false",
    }

    if settings.output_dir:
        os.makedirs(settings.output_dir, exist_ok=True)
        fname = f"{spec.uid}-{uuid.uuid4().hex[:8]}.png"
        with open(os.path.join(settings.output_dir, fname), "wb") as fh:
            fh.write(png)
        headers["X-Saved-As"] = fname

    return Response(content=png, media_type="image/png", headers=headers)


@router.get("/config")
async def get_config():
    """Expose the active pipeline configuration (handy for the client tier)."""
    return JSONResponse({
        "ai_provider": settings.ai_provider,
        "max_cost_inr": settings.max_cost_inr,
        "bill_api_cost_to_client": settings.bill_api_cost_to_client,
        "flux_configured": bool(settings.fal_api_key or settings.replicate_api_token),
    })
