"""AILicet 노드락 라이선스 오프라인 검증 (docs/nodelock-license-usage.md §4).

서명 대상은 base64 디코드 전의 ``data`` 문자열이다. 서명 통과 전에는 페이로드를 신뢰하지 않는다.
"""
from __future__ import annotations

import base64
import binascii
import hashlib
import json
from dataclasses import dataclass, field
from datetime import date, datetime, timezone
from typing import Any

from nacl.exceptions import BadSignatureError
from nacl.signing import VerifyKey


@dataclass(frozen=True)
class VerifyResult:
    ok: bool
    reason: str
    payload: dict[str, Any] = field(default_factory=dict)


def host_id_from_machine_value(raw: str) -> str:
    """원시 머신 UUID(IOPlatformUUID / MachineGuid / machine-id) → ``XXXX-XXXX-XXXX-XXXX``."""
    normalized = raw.strip().upper()
    digest = hashlib.sha256(f"AILICET-NODELOCK-v1:{normalized}".encode()).hexdigest()[:16].upper()
    return "-".join(digest[i : i + 4] for i in range(0, 16, 4))


def _fail(reason: str) -> VerifyResult:
    return VerifyResult(False, reason)


def verify_license_file(
    file_text: str,
    public_key_base64: str,
    local_host_id: str,
    today: str | None = None,
) -> VerifyResult:
    """``today`` 는 ``YYYY-MM-DD``(UTC). 생략하면 UTC 오늘."""
    today = today or datetime.now(timezone.utc).date().isoformat()

    try:
        verify_key = VerifyKey(base64.b64decode(public_key_base64, validate=True))
        envelope = json.loads(file_text)
    except (ValueError, binascii.Error):
        return _fail("INVALID_FORMAT")
    if not isinstance(envelope, dict):
        return _fail("INVALID_FORMAT")
    if envelope.get("alg") != "Ed25519":
        return _fail("INVALID_ALG")

    data, sig_b64 = envelope.get("data"), envelope.get("sig")
    if not isinstance(data, str) or not isinstance(sig_b64, str):
        return _fail("INVALID_FORMAT")
    try:
        sig = base64.b64decode(sig_b64, validate=True)
        verify_key.verify(data.encode(), sig)
    except BadSignatureError:
        return _fail("TAMPERED")
    except (ValueError, binascii.Error):
        return _fail("INVALID_FORMAT")

    try:
        payload = json.loads(base64.b64decode(data, validate=True))
    except (ValueError, binascii.Error):
        return _fail("INVALID_FORMAT")
    if not isinstance(payload, dict):
        return _fail("INVALID_FORMAT")

    if payload.get("magic") != "AILICET":
        return _fail("NOT_AILICET")
    if payload.get("payload_version") != 1:
        return _fail("UNSUPPORTED_VERSION")
    if payload.get("license_type") != "nodelock":
        return _fail("NOT_NODELOCK")
    if payload.get("system_id_check") is True and payload.get("host_id") != local_host_id:
        return _fail("HOST_MISMATCH")
    expire = payload.get("expire_date")
    if expire is not None and expire < today:
        return _fail("EXPIRED")

    return VerifyResult(True, "OK", payload)
