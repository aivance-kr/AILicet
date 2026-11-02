package kr.aivance.ailicet;

import com.google.gson.JsonObject;

/** 검증 결과. 성공이면 {@code reason == "OK"} 이고 payload 가 채워진다. */
public record VerifyResult(boolean ok, String reason, JsonObject payload) {
    static VerifyResult fail(String reason) {
        return new VerifyResult(false, reason, new JsonObject());
    }
}
