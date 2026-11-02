package kr.aivance.ailicet;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.Base64;
import org.junit.jupiter.api.Test;

class NodeLockTest {
    private static final Path FIXTURES = Path.of(System.getProperty("fixtures"));
    private static final String HOST_ID = "7121-0B91-F5B6-AA1B";
    private final String publicKey = read("public_key.txt").strip();
    private final String license = read("NLicense.lic");

    private static String read(String name) {
        try {
            return Files.readString(FIXTURES.resolve(name));
        } catch (java.io.IOException e) {
            throw new IllegalStateException(e);
        }
    }

    @Test
    void hostIdFixedVector() {
        assertEquals(HOST_ID, NodeLock.hostIdFromMachineValue(" 564d0102-0304-0506-0708-090a0b0c0d0e "));
    }

    @Test
    void validLicense() {
        VerifyResult r = NodeLock.verify(license, publicKey, HOST_ID, "2026-11-02");
        assertTrue(r.ok());
        assertEquals(2, r.payload().getAsJsonArray("modules").size());
    }

    @Test
    void hostMismatchAndExpired() {
        assertEquals("HOST_MISMATCH", NodeLock.verify(license, publicKey, "0000-0000-0000-0000", "2026-11-02").reason());
        assertEquals("EXPIRED", NodeLock.verify(license, publicKey, HOST_ID, "2100-01-01").reason());
    }

    @Test
    void tamperedData() {
        JsonObject env = JsonParser.parseString(license).getAsJsonObject();
        JsonObject payload = JsonParser.parseString(new String(Base64.getDecoder().decode(env.get("data").getAsString()), StandardCharsets.UTF_8)).getAsJsonObject();
        payload.addProperty("expire_date", "2999-01-01");
        env.addProperty("data", Base64.getEncoder().encodeToString(payload.toString().getBytes(StandardCharsets.UTF_8)));
        assertEquals("TAMPERED", NodeLock.verify(env.toString(), publicKey, HOST_ID, "2026-11-02").reason());
    }

    @Test
    void garbageAndWrongAlg() {
        assertEquals("INVALID_FORMAT", NodeLock.verify("not json", publicKey, HOST_ID).reason());
        JsonObject env = JsonParser.parseString(license).getAsJsonObject();
        env.addProperty("alg", "HS256");
        assertEquals("INVALID_ALG", NodeLock.verify(env.toString(), publicKey, HOST_ID).reason());
    }
}
