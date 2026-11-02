#pragma once

#include <nlohmann/json.hpp>
#include <string>

namespace ailicet {

/// 검증 결과. 성공이면 reason == "OK" 이고 payload 가 채워진다.
struct VerifyResult {
    bool ok = false;
    std::string reason;
    nlohmann::json payload = nlohmann::json::object();
};

/// 원시 머신 UUID(IOPlatformUUID / MachineGuid / machine-id) -> "XXXX-XXXX-XXXX-XXXX".
/// 내부에서 sodium_init() 을 호출하므로 별도 초기화는 필요 없다.
std::string host_id_from_machine_value(const std::string& raw);

/// 노드락 라이선스 오프라인 검증 (docs/nodelock-license-usage.md §4).
/// @param today "YYYY-MM-DD"(UTC). 비어 있으면 UTC 오늘.
VerifyResult verify_license_file(const std::string& file_text,
                                 const std::string& public_key_base64,
                                 const std::string& local_host_id,
                                 const std::string& today = "");

}  // namespace ailicet
