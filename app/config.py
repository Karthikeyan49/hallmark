"""Runtime configuration for the compliance-image pipeline.

All knobs are environment-driven so the same build runs the free prototype
today and the paid AI tier later without code changes.
"""
from __future__ import annotations

import os
from dataclasses import dataclass


def _env_bool(name: str, default: bool = False) -> bool:
    val = os.getenv(name)
    if val is None:
        return default
    return val.strip().lower() in {"1", "true", "yes", "on"}


@dataclass(frozen=True)
class Settings:
    # Standardised composite geometry (pixels).
    pane_width: int = 512
    pane_height: int = 512
    margin: int = 24
    header_height: int = 96
    footer_height: int = 56

    # Which provider powers the *model/product* pane of Track A.
    #   "passthrough" -> use the uploaded image, lightly enhanced (0 rupees, no deps)
    #   "rembg"       -> local background removal (0 rupees, optional dependency)
    #   "flux"        -> hosted FLUX.1 [schnell] generation (~0.25 rupees/image)
    ai_provider: str = os.getenv("HALLMARK_AI_PROVIDER", "passthrough")

    # Per-image cost ceiling enforced by the pipeline (in INR). The report's
    # requirement is < 1 rupee per image; we default the guard to 1.0.
    max_cost_inr: float = float(os.getenv("HALLMARK_MAX_COST_INR", "1.0"))

    # Hosted-generation credentials (only needed when ai_provider == "flux").
    fal_api_key: str | None = os.getenv("FAL_KEY")
    replicate_api_token: str | None = os.getenv("REPLICATE_API_TOKEN")

    # When True, the API-tier cost is billed to the client (report section 4).
    bill_api_cost_to_client: bool = _env_bool("HALLMARK_BILL_CLIENT", True)

    # Where generated composites are persisted (empty -> not persisted).
    output_dir: str = os.getenv("HALLMARK_OUTPUT_DIR", "output")


settings = Settings()
