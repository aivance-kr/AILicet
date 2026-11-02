import base64
import json
from pathlib import Path

from ailicet import host_id_from_machine_value, verify_license_file

FIXTURES = Path(__file__).resolve().parents[2] / "testdata"
PUBLIC_KEY = (FIXTURES / "public_key.txt").read_text().strip()
LICENSE = (FIXTURES / "NLicense.lic").read_text()
HOST_ID = "7121-0B91-F5B6-AA1B"


def test_host_id_fixed_vector():
    assert host_id_from_machine_value(" 564d0102-0304-0506-0708-090a0b0c0d0e ") == HOST_ID


def test_valid_license():
    result = verify_license_file(LICENSE, PUBLIC_KEY, HOST_ID, "2026-11-02")
    assert result.ok and result.payload["modules"] == ["MD001", "MD002"]


def test_host_mismatch():
    assert verify_license_file(LICENSE, PUBLIC_KEY, "0000-0000-0000-0000", "2026-11-02").reason == "HOST_MISMATCH"


def test_expired():
    assert verify_license_file(LICENSE, PUBLIC_KEY, HOST_ID, "2100-01-01").reason == "EXPIRED"


def test_tampered_data():
    envelope = json.loads(LICENSE)
    payload = json.loads(base64.b64decode(envelope["data"]))
    payload["expire_date"] = None
    envelope["data"] = base64.b64encode(json.dumps(payload).encode()).decode()
    assert verify_license_file(json.dumps(envelope), PUBLIC_KEY, HOST_ID).reason == "TAMPERED"


def test_garbage_and_wrong_alg():
    assert verify_license_file("not json", PUBLIC_KEY, HOST_ID).reason == "INVALID_FORMAT"
    envelope = {**json.loads(LICENSE), "alg": "HS256"}
    assert verify_license_file(json.dumps(envelope), PUBLIC_KEY, HOST_ID).reason == "INVALID_ALG"
