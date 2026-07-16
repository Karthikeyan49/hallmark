from PIL import Image

from app.config import settings
from app.services import ai_generator, compositor, image_utils as iu


def _spec(uid_image=None):
    return compositor.ItemSpec(
        uid="HUID-7A2C9K",
        weight_grams=2.0,
        model_image=Image.new("RGB", (400, 400), (200, 170, 120)),
        uid_image=uid_image,
        company="Acme Jewels",
        item_type="Ring",
    )


def test_track_a_without_uid_image():
    result = compositor.build_composite(_spec())
    assert result.track == "A"
    assert result.cost_inr == 0.0
    assert result.billable_to_client is False
    # 3 panes + 4 margins wide.
    expected_w = 3 * settings.pane_width + 4 * settings.margin
    assert result.image.width == expected_w
    assert result.image.height > settings.pane_height


def test_track_b_with_uid_image():
    uid_img = Image.new("RGB", (300, 120), (60, 60, 64))
    result = compositor.build_composite(_spec(uid_image=uid_img))
    assert result.track == "B"


def test_weight_is_rendered_deterministically():
    # Same weight => byte-identical weight pane text; engine must not vary.
    a = compositor.build_composite(_spec())
    b = compositor.build_composite(_spec())
    assert iu.to_png_bytes(a.image) == iu.to_png_bytes(b.image)


def test_cost_ceiling_forces_free_fallback(monkeypatch):
    # A provider priced above the 1 INR ceiling must fall back to free.
    monkeypatch.setattr(ai_generator.FluxProvider, "cost_inr", 5.0)
    res = ai_generator.get_model_pane(
        Image.new("RGB", (100, 100), (255, 255, 255)), provider="flux")
    assert res.provider == "passthrough"
    assert res.cost_inr == 0.0


def test_flux_stays_under_one_rupee():
    # The wired generative tier must respect the < 1 INR requirement.
    assert ai_generator.FluxProvider.cost_inr < 1.0


def test_provider_fallback_never_raises_for_unconfigured_flux():
    res = ai_generator.get_model_pane(
        Image.new("RGB", (100, 100), (255, 255, 255)), provider="flux")
    # No FAL/Replicate key in test env -> silent fall back to free provider.
    assert res.provider == "passthrough"
