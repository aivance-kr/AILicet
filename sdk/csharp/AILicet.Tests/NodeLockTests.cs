using System.Text;
using System.Text.Json.Nodes;
using Xunit;

namespace AILicet.Tests;

public class NodeLockTests
{
    private const string HostId = "7121-0B91-F5B6-AA1B";
    private static readonly string PublicKey = File.ReadAllText("testdata/public_key.txt").Trim();
    private static readonly string License = File.ReadAllText("testdata/NLicense.lic");

    [Fact]
    public void HostIdFixedVector() =>
        Assert.Equal(HostId, NodeLock.HostIdFromMachineValue(" 564d0102-0304-0506-0708-090a0b0c0d0e "));

    [Fact]
    public void ValidLicense()
    {
        var r = NodeLock.Verify(License, PublicKey, HostId, "2026-11-02");
        Assert.True(r.Ok);
        Assert.Equal(2, r.Payload!["modules"]!.AsArray().Count);
    }

    [Fact]
    public void HostMismatchAndExpired()
    {
        Assert.Equal("HOST_MISMATCH", NodeLock.Verify(License, PublicKey, "0000-0000-0000-0000", "2026-11-02").Reason);
        Assert.Equal("EXPIRED", NodeLock.Verify(License, PublicKey, HostId, "2100-01-01").Reason);
    }

    [Fact]
    public void TamperedData()
    {
        var env = JsonNode.Parse(License)!.AsObject();
        var payload = JsonNode.Parse(Convert.FromBase64String(env["data"]!.GetValue<string>()))!.AsObject();
        payload["expire_date"] = "2999-01-01";
        env["data"] = Convert.ToBase64String(Encoding.UTF8.GetBytes(payload.ToJsonString()));
        Assert.Equal("TAMPERED", NodeLock.Verify(env.ToJsonString(), PublicKey, HostId, "2026-11-02").Reason);
    }

    [Fact]
    public void GarbageAndWrongAlg()
    {
        Assert.Equal("INVALID_FORMAT", NodeLock.Verify("not json", PublicKey, HostId).Reason);
        var env = JsonNode.Parse(License)!.AsObject();
        env["alg"] = "HS256";
        Assert.Equal("INVALID_ALG", NodeLock.Verify(env.ToJsonString(), PublicKey, HostId).Reason);
    }
}
