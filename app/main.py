"""FastAPI entrypoint for the Hallmark image-automation module.

Run locally:
    uvicorn app.main:app --reload
Then open http://127.0.0.1:8000/ for the upload UI.
"""
from __future__ import annotations

import os

from fastapi import FastAPI
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles

from . import __version__
from .routers import compliance

app = FastAPI(
    title="Hallmark — Image Processing & AI Automation",
    version=__version__,
    description="Dual-track compliance-image pipeline (Track A + Track B).",
)

app.include_router(compliance.router)

_STATIC_DIR = os.path.join(os.path.dirname(__file__), "static")


@app.get("/health")
async def health():
    return {"status": "ok", "version": __version__}


@app.get("/")
async def index():
    return FileResponse(os.path.join(_STATIC_DIR, "index.html"))


app.mount("/static", StaticFiles(directory=_STATIC_DIR), name="static")
