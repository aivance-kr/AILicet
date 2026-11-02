#include <ailicet/nodelock.hpp>
#include <sodium.h>

#include <cctype>
#include <cstdlib>
#include <cstring>
#include <fstream>
#include <iostream>
#include <sstream>

#ifndef FIXTURES_DIR
#error "FIXTURES_DIR 가 필요하다 (sdk/testdata 경로)"
#endif

static int failures = 0;

#define CHECK_EQ(actual, expected)                                                              \
    do {                                                                                        \
        if (!((actual) == (expected))) {                                                        \
            ++failures;                                                                         \
            std::cerr << __FILE__ << ":" << __LINE__ << " " #actual " != " #expected << "\n";   \
        }                                                                                       \
    } while (0)

static std::string read_file(const std::string& name) {
    std::ifstream in(std::string(FIXTURES_DIR) + "/" + name);
    std::stringstream ss;
    ss << in.rdbuf();
    return ss.str();
}

static std::string trim(std::string s) {
    while (!s.empty() && std::isspace(static_cast<unsigned char>(s.back()))) s.pop_back();
    return s;
}

static std::string base64(const std::string& s) {
    std::string out(sodium_base64_ENCODED_LEN(s.size(), sodium_base64_VARIANT_ORIGINAL), '\0');
    sodium_bin2base64(out.data(), out.size(), reinterpret_cast<const unsigned char*>(s.data()), s.size(),
                      sodium_base64_VARIANT_ORIGINAL);
    out.resize(std::strlen(out.c_str()));
    return out;
}

int main() {
    using namespace ailicet;
    const std::string host = "7121-0B91-F5B6-AA1B";
    const std::string pub = trim(read_file("public_key.txt"));
    const std::string lic = read_file("NLicense.lic");

    CHECK_EQ(host_id_from_machine_value(" 564d0102-0304-0506-0708-090a0b0c0d0e "), host);

    auto ok = verify_license_file(lic, pub, host, "2026-11-02");
    CHECK_EQ(ok.ok, true);
    CHECK_EQ(ok.payload["modules"].size(), static_cast<size_t>(2));

    CHECK_EQ(verify_license_file(lic, pub, "0000-0000-0000-0000", "2026-11-02").reason, std::string("HOST_MISMATCH"));
    CHECK_EQ(verify_license_file(lic, pub, host, "2100-01-01").reason, std::string("EXPIRED"));

    // data 의 페이로드를 바꾸고 원래 서명을 유지하면 TAMPERED 여야 한다
    auto env = nlohmann::json::parse(lic);
    const std::string data = env["data"].get<std::string>();
    std::string raw(data.size(), '\0');
    size_t len = 0;
    sodium_base642bin(reinterpret_cast<unsigned char*>(raw.data()), raw.size(), data.c_str(), data.size(), nullptr,
                      &len, nullptr, sodium_base64_VARIANT_ORIGINAL);
    auto payload = nlohmann::json::parse(raw.substr(0, len));
    payload["expire_date"] = "2999-01-01";
    env["data"] = base64(payload.dump());
    CHECK_EQ(verify_license_file(env.dump(), pub, host, "2026-11-02").reason, std::string("TAMPERED"));

    CHECK_EQ(verify_license_file("not json", pub, host).reason, std::string("INVALID_FORMAT"));
    env = nlohmann::json::parse(lic);
    env["alg"] = "HS256";
    CHECK_EQ(verify_license_file(env.dump(), pub, host).reason, std::string("INVALID_ALG"));

    if (failures == 0) std::cout << "OK\n";
    return failures == 0 ? 0 : 1;
}
