package kr.aivance.ailicet;

import com.google.gson.JsonElement;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import java.nio.charset.StandardCharsets;
import java.security.GeneralSecurityException;
import java.security.KeyFactory;
import java.security.MessageDigest;
import java.security.PublicKey;
import java.security.Signature;
import java.security.spec.X509EncodedKeySpec;
import java.time.LocalDate;
import java.time.ZoneOffset;
import java.util.Base64;
import java.util.HexFormat;

/**
 * AILicet 노드락 라이선스 오프라인 검증 (docs/nodelock-license-usage.md §4).
 * 서명 대상은 base64 디코드 전의 {@code data} 문자열이며, 서명 통과 전에는 페이로드를 신뢰하지 않는다.
 */
public final class NodeLock {
    /** Ed25519 SubjectPublicKeyInfo DER 헤더. 뒤에 32바이트 원시 공개키가 붙는다. */
    private static final byte[] ED25519_X509_PREFIX = HexFormat.of().parseHex("302a300506032b6570032100");

    private NodeLock() {}

    /** 원시 머신 UUID(IOPlatformUUID / MachineGuid / machine-id) → {@code XXXX-XXXX-XXXX-XXXX}. */
    public static String hostIdFromMachineValue(String raw) {
        try {
            String normalized = raw.strip().toUpperCase(java.util.Locale.ROOT);
            byte[] hash = MessageDigest.getInstance("SHA-256")
                    .digest(("AILICET-NODELOCK-v1:" + normalized).getBytes(StandardCharsets.UTF_8));
            String hex = HexFormat.of().withUpperCase().formatHex(hash, 0, 8);
            return hex.substring(0, 4) + "-" + hex.substring(4, 8) + "-" + hex.substring(8, 12) + "-" + hex.substring(12, 16);
        } catch (GeneralSecurityException e) {
            throw new IllegalStateException(e);
        }
    }

    public static VerifyResult verify(String fileText, String publicKeyBase64, String localHostId) {
        return verify(fileText, publicKeyBase64, localHostId, LocalDate.now(ZoneOffset.UTC).toString());
    }

    /** @param today {@code YYYY-MM-DD}(UTC) */
    public static VerifyResult verify(String fileText, String publicKeyBase64, String localHostId, String today) {
        JsonObject envelope;
        PublicKey publicKey;
        try {
            publicKey = toPublicKey(Base64.getDecoder().decode(publicKeyBase64));
            envelope = JsonParser.parseString(fileText).getAsJsonObject();
        } catch (RuntimeException | GeneralSecurityException e) {
            return VerifyResult.fail("INVALID_FORMAT");
        }
        if (!"Ed25519".equals(str(envelope, "alg"))) {
            return VerifyResult.fail("INVALID_ALG");
        }

        String data = str(envelope, "data");
        String sigB64 = str(envelope, "sig");
        if (data == null || sigB64 == null) {
            return VerifyResult.fail("INVALID_FORMAT");
        }
        try {
            Signature verifier = Signature.getInstance("Ed25519");
            verifier.initVerify(publicKey);
            verifier.update(data.getBytes(StandardCharsets.UTF_8));
            if (!verifier.verify(Base64.getDecoder().decode(sigB64))) {
                return VerifyResult.fail("TAMPERED");
            }
        } catch (IllegalArgumentException e) {
            return VerifyResult.fail("INVALID_FORMAT");
        } catch (GeneralSecurityException e) {
            return VerifyResult.fail("TAMPERED");
        }

        JsonObject payload;
        try {
            String json = new String(Base64.getDecoder().decode(data), StandardCharsets.UTF_8);
            payload = JsonParser.parseString(json).getAsJsonObject();
        } catch (RuntimeException e) {
            return VerifyResult.fail("INVALID_FORMAT");
        }

        if (!"AILICET".equals(str(payload, "magic"))) return VerifyResult.fail("NOT_AILICET");
        JsonElement version = payload.get("payload_version");
        if (version == null || !version.isJsonPrimitive() || version.getAsInt() != 1) {
            return VerifyResult.fail("UNSUPPORTED_VERSION");
        }
        if (!"nodelock".equals(str(payload, "license_type"))) return VerifyResult.fail("NOT_NODELOCK");

        JsonElement check = payload.get("system_id_check");
        boolean idCheck = check != null && check.isJsonPrimitive() && check.getAsBoolean();
        if (idCheck && !localHostId.equals(str(payload, "host_id"))) return VerifyResult.fail("HOST_MISMATCH");

        String expire = str(payload, "expire_date");
        if (expire != null && expire.compareTo(today) < 0) return VerifyResult.fail("EXPIRED");

        return new VerifyResult(true, "OK", payload);
    }

    private static PublicKey toPublicKey(byte[] raw) throws GeneralSecurityException {
        if (raw.length != 32) {
            throw new IllegalArgumentException("Ed25519 공개키는 32바이트여야 한다");
        }
        byte[] der = new byte[ED25519_X509_PREFIX.length + raw.length];
        System.arraycopy(ED25519_X509_PREFIX, 0, der, 0, ED25519_X509_PREFIX.length);
        System.arraycopy(raw, 0, der, ED25519_X509_PREFIX.length, raw.length);
        return KeyFactory.getInstance("Ed25519").generatePublic(new X509EncodedKeySpec(der));
    }

    private static String str(JsonObject o, String key) {
        JsonElement e = o.get(key);
        return e != null && e.isJsonPrimitive() && e.getAsJsonPrimitive().isString() ? e.getAsString() : null;
    }
}
