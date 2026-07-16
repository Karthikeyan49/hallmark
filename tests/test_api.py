from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_health():
    r = client.get("/health")
    assert r.status_code == 200
    assert r.json()["status"] == "ok"


def test_composite_track_a(model_png):
    r = client.post(
        "/api/composite",
        data={"uid": "HUID-7A2C9K", "weight_grams": "2.0", "item_type": "Ring"},
        files={"model_image": ("m.png", model_png, "image/png")},
    )
    assert r.status_code == 200
    assert r.headers["content-type"] == "image/png"
    assert r.headers["X-Compliance-Track"] == "A"
    assert float(r.headers["X-Cost-INR"]) < 1.0


def test_composite_track_b(model_png, uid_png):
    r = client.post(
        "/api/composite",
        data={"uid": "HUID-7A2C9K", "weight_grams": "15.35"},
        files={
            "model_image": ("m.png", model_png, "image/png"),
            "uid_image": ("u.png", uid_png, "image/png"),
        },
    )
    assert r.status_code == 200
    assert r.headers["X-Compliance-Track"] == "B"


def test_rejects_bad_uid(model_png):
    r = client.post(
        "/api/composite",
        data={"uid": "no spaces allowed!", "weight_grams": "2.0"},
        files={"model_image": ("m.png", model_png, "image/png")},
    )
    assert r.status_code == 422


def test_rejects_nonpositive_weight(model_png):
    r = client.post(
        "/api/composite",
        data={"uid": "HUID-1", "weight_grams": "0"},
        files={"model_image": ("m.png", model_png, "image/png")},
    )
    assert r.status_code == 422


def test_config_endpoint():
    r = client.get("/api/config")
    assert r.status_code == 200
    body = r.json()
    assert "ai_provider" in body
    assert body["max_cost_inr"] == 1.0
