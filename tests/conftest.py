from io import BytesIO

import pytest
from PIL import Image


def _png_bytes(color=(200, 170, 120), size=(400, 400)) -> bytes:
    buf = BytesIO()
    Image.new("RGB", size, color).save(buf, format="PNG")
    return buf.getvalue()


@pytest.fixture
def model_png() -> bytes:
    return _png_bytes()


@pytest.fixture
def uid_png() -> bytes:
    return _png_bytes(color=(60, 60, 64), size=(300, 120))
