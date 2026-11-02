#include "ailicet/nodelock.hpp"

#include <sodium.h>

#include <algorithm>
#include <cctype>
#include <ctime>
#include <vector>

namespace ailicet {
namespace {

using nlohmann::json;

VerifyResult fail(const char* reason) {
    VerifyResult r;
    r.reason = reason;
    return r;
}

bool decode_base64(const std::string& in, std::vector<unsigned char>& out) {
    out.assign(in.size() + 1, 0);  // 디코드 결과는 입력보다 길 수 없다
    size_t len = 0;
    if (sodium_base642bin(out.data(), out.size(), in.c_str(), in.size(), nullptr, &len, nullptr,
                          sodium_base64_VARIANT_ORIGINAL) != 0) {
        return false;
    }
    out.resize(len);
    return true;
}

std::string utc_today() {
    std::time_t now = std::time(nullptr);
    std::tm tm{};
#ifdef _WIN32
    gmtime_s(&tm, &now);
#else
    gmtime_r(&now, &tm);
#endif
    char buf[16];
    std::strftime(buf, sizeof buf, "%Y-%m-%d", &tm);
    return buf;
}

const std::string* get_string(const json& o, const char* key) {
    auto it = o.find(key);
    return it != o.end() && it->is_string() ? it->get_ptr<const std::string*>() : nullptr;
}

}  // namespace

std::string host_id_from_machine_value(const std::string& raw) {
    if (sodium_init() < 0) return "";

    auto first = std::find_if_not(raw.begin(), raw.end(), [](unsigned char c) { return std::isspace(c); });
    auto last = std::find_if_not(raw.rbegin(), raw.rend(), [](unsigned char c) { return std::isspace(c); }).base();
    std::string normalized = first < last ? std::string(first, last) : std::string();
    std::transform(normalized.begin(), normalized.end(), normalized.begin(),
                   [](unsigned char c) { return static_cast<char>(std::toupper(c)); });

    const std::string input = "AILICET-NODELOCK-v1:" + normalized;
    unsigned char hash[crypto_hash_sha256_BYTES];
    crypto_hash_sha256(hash, reinterpret_cast<const unsigned char*>(input.data()), input.size());

    static const char* digits = "0123456789ABCDEF";
    std::string id;
    for (int i = 0; i < 8; ++i) {
        if (i > 0 && i % 2 == 0) id += '-';
        id += digits[hash[i] >> 4];
        id += digits[hash[i] & 0x0F];
    }
    return id;
}

VerifyResult verify_license_file(const std::string& file_text, const std::string& public_key_base64,
                                 const std::string& local_host_id, const std::string& today_in) {
    if (sodium_init() < 0) return fail("INVALID_FORMAT");
    const std::string today = today_in.empty() ? utc_today() : today_in;

    std::vector<unsigned char> public_key;
    if (!decode_base64(public_key_base64, public_key) || public_key.size() != crypto_sign_PUBLICKEYBYTES) {
        return fail("INVALID_FORMAT");
    }
    json envelope = json::parse(file_text, nullptr, /*allow_exceptions=*/false);
    if (!envelope.is_object()) return fail("INVALID_FORMAT");

    const std::string* alg = get_string(envelope, "alg");
    if (alg == nullptr || *alg != "Ed25519") return fail("INVALID_ALG");

    const std::string* data = get_string(envelope, "data");
    const std::string* sig_b64 = get_string(envelope, "sig");
    std::vector<unsigned char> sig;
    if (data == nullptr || sig_b64 == nullptr || !decode_base64(*sig_b64, sig)) return fail("INVALID_FORMAT");

    // 서명 대상은 base64 디코드 전의 data 문자열 그대로다
    if (sig.size() != crypto_sign_BYTES ||
        crypto_sign_verify_detached(sig.data(), reinterpret_cast<const unsigned char*>(data->data()),
                                    data->size(), public_key.data()) != 0) {
        return fail("TAMPERED");
    }

    std::vector<unsigned char> payload_bytes;
    if (!decode_base64(*data, payload_bytes)) return fail("INVALID_FORMAT");
    json payload = json::parse(payload_bytes.begin(), payload_bytes.end(), nullptr, false);
    if (!payload.is_object()) return fail("INVALID_FORMAT");

    const std::string* magic = get_string(payload, "magic");
    if (magic == nullptr || *magic != "AILICET") return fail("NOT_AILICET");
    auto version = payload.find("payload_version");
    if (version == payload.end() || !version->is_number_integer() || version->get<int>() != 1) {
        return fail("UNSUPPORTED_VERSION");
    }
    const std::string* type = get_string(payload, "license_type");
    if (type == nullptr || *type != "nodelock") return fail("NOT_NODELOCK");

    auto check = payload.find("system_id_check");
    const bool id_check = check != payload.end() && check->is_boolean() && check->get<bool>();
    const std::string* host_id = get_string(payload, "host_id");
    if (id_check && (host_id == nullptr || *host_id != local_host_id)) return fail("HOST_MISMATCH");

    const std::string* expire = get_string(payload, "expire_date");
    if (expire != nullptr && *expire < today) return fail("EXPIRED");

    VerifyResult ok;
    ok.ok = true;
    ok.reason = "OK";
    ok.payload = std::move(payload);
    return ok;
}

}  // namespace ailicet
