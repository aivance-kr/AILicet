# AILicet SDK

노드락 라이선스 파일(`NLicense.lic`)을 클라이언트에서 오프라인 검증하는 SDK. 모든 언어가
[`docs/nodelock-license-usage.md`](../docs/nodelock-license-usage.md) §4 의 검증 순서를 동일하게 구현한다.

| 언어 | 경로 | 암호 라이브러리 | 상태 |
|------|------|----------------|------|
| Python | [`python/`](python) | PyNaCl | 테스트 통과 |
| Java | [`java/`](java) | JDK 17 내장 Ed25519 (+Gson) | 테스트 통과 |
| C++ | [`cpp/`](cpp) | libsodium (+nlohmann/json) | 테스트 통과 |
| C# | [`csharp/`](csharp) | BouncyCastle | 테스트 통과 |
| PHP | [`frontapi/src/Support/LicenseVerifier.php`](../frontapi/src/Support/LicenseVerifier.php) | ext-sodium | 참조 구현 |

## 공통 API

- `verify(file, publicKeyBase64, localHostId, today?)` → `{ok, reason, payload}`
- `hostIdFromMachineValue(raw)` — 원시 머신 UUID → `XXXX-XXXX-XXXX-XXXX`
  (원시값 취득은 호출자 몫: macOS `IOPlatformUUID` / Windows `MachineGuid` / 리눅스 `/etc/machine-id`)
- `reason`: `OK` `INVALID_FORMAT` `INVALID_ALG` `TAMPERED` `NOT_AILICET` `UNSUPPORTED_VERSION`
  `NOT_NODELOCK` `HOST_MISMATCH` `EXPIRED`
- 온라인 유효성 확인(정지·폐기)은 SDK 범위 밖이며 frontapi REST 로 호출한다(§5).

## 테스트

모든 SDK 가 [`testdata/`](testdata) 의 같은 픽스처로 검증한다.

```bash
cd sdk/python && pip install -e '.[test]' && pytest
cd sdk/java   && gradle test
cd sdk/csharp && dotnet test AILicet.Tests
cd sdk/cpp    && cmake -B build && cmake --build build && ctest --test-dir build
```
