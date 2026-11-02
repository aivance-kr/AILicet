using System.Globalization;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Text.Json.Nodes;
using Org.BouncyCastle.Math.EC.Rfc8032;

namespace AILicet;

/// <summary>검증 결과. 성공이면 <c>Reason == "OK"</c> 이고 <c>Payload</c> 가 채워진다.</summary>
public sealed record VerifyResult(bool Ok, string Reason, JsonObject? Payload = null)
{
    internal static VerifyResult Fail(string reason) => new(false, reason);
}

/// <summary>
/// AILicet 노드락 라이선스 오프라인 검증 (docs/nodelock-license-usage.md §4).
/// 서명 대상은 base64 디코드 전의 <c>data</c> 문자열이며, 서명 통과 전에는 페이로드를 신뢰하지 않는다.
/// </summary>
public static class NodeLock
{
    /// <summary>원시 머신 UUID(IOPlatformUUID / MachineGuid / machine-id) → <c>XXXX-XXXX-XXXX-XXXX</c>.</summary>
    public static string HostIdFromMachineValue(string raw)
    {
        var normalized = raw.Trim().ToUpperInvariant();
        var hash = SHA256.HashData(Encoding.UTF8.GetBytes("AILICET-NODELOCK-v1:" + normalized));
        var hex = Convert.ToHexString(hash, 0, 8);
        return $"{hex[..4]}-{hex[4..8]}-{hex[8..12]}-{hex[12..16]}";
    }

    /// <param name="today"><c>YYYY-MM-DD</c>(UTC). 생략하면 UTC 오늘.</param>
    public static VerifyResult Verify(string fileText, string publicKeyBase64, string localHostId, string? today = null)
    {
        today ??= DateTime.UtcNow.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture);

        byte[] publicKey;
        JsonObject? envelope;
        try
        {
            publicKey = Convert.FromBase64String(publicKeyBase64);
            envelope = JsonNode.Parse(fileText) as JsonObject;
        }
        catch (Exception e) when (e is FormatException or JsonException)
        {
            return VerifyResult.Fail("INVALID_FORMAT");
        }
        if (envelope is null || publicKey.Length != Ed25519.PublicKeySize) return VerifyResult.Fail("INVALID_FORMAT");
        if (Str(envelope, "alg") != "Ed25519") return VerifyResult.Fail("INVALID_ALG");

        var data = Str(envelope, "data");
        var sigB64 = Str(envelope, "sig");
        if (data is null || sigB64 is null) return VerifyResult.Fail("INVALID_FORMAT");

        byte[] sig;
        try
        {
            sig = Convert.FromBase64String(sigB64);
        }
        catch (FormatException)
        {
            return VerifyResult.Fail("INVALID_FORMAT");
        }
        if (sig.Length != Ed25519.SignatureSize) return VerifyResult.Fail("TAMPERED");

        var message = Encoding.UTF8.GetBytes(data);
        if (!Ed25519.Verify(sig, 0, publicKey, 0, message, 0, message.Length)) return VerifyResult.Fail("TAMPERED");

        JsonObject? payload;
        try
        {
            payload = JsonNode.Parse(Convert.FromBase64String(data)) as JsonObject;
        }
        catch (Exception e) when (e is FormatException or JsonException)
        {
            return VerifyResult.Fail("INVALID_FORMAT");
        }
        if (payload is null) return VerifyResult.Fail("INVALID_FORMAT");

        if (Str(payload, "magic") != "AILICET") return VerifyResult.Fail("NOT_AILICET");
        if (!(payload["payload_version"] is JsonValue v && v.TryGetValue<int>(out var ver) && ver == 1))
            return VerifyResult.Fail("UNSUPPORTED_VERSION");
        if (Str(payload, "license_type") != "nodelock") return VerifyResult.Fail("NOT_NODELOCK");

        var idCheck = payload["system_id_check"] is JsonValue c && c.TryGetValue<bool>(out var b) && b;
        if (idCheck && Str(payload, "host_id") != localHostId) return VerifyResult.Fail("HOST_MISMATCH");

        var expire = Str(payload, "expire_date");
        if (expire is not null && string.CompareOrdinal(expire, today) < 0) return VerifyResult.Fail("EXPIRED");

        return new VerifyResult(true, "OK", payload);
    }

    private static string? Str(JsonObject o, string key) =>
        o[key] is JsonValue v && v.TryGetValue<string>(out var s) ? s : null;
}
