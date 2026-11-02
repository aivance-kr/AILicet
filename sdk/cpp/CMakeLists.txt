cmake_minimum_required(VERSION 3.16)
project(ailicet_sdk VERSION 0.1.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(PkgConfig REQUIRED)
pkg_check_modules(SODIUM REQUIRED IMPORTED_TARGET libsodium)

find_package(nlohmann_json 3.2.0 QUIET)
if(NOT nlohmann_json_FOUND)
  include(FetchContent)
  FetchContent_Declare(json URL https://github.com/nlohmann/json/releases/download/v3.11.3/json.tar.xz)
  FetchContent_MakeAvailable(json)
endif()

add_library(ailicet src/nodelock.cpp)
target_include_directories(ailicet PUBLIC include)
target_link_libraries(ailicet PUBLIC PkgConfig::SODIUM nlohmann_json::nlohmann_json)

enable_testing()
add_executable(test_nodelock tests/test_nodelock.cpp)
target_link_libraries(test_nodelock PRIVATE ailicet)
target_compile_definitions(test_nodelock PRIVATE FIXTURES_DIR="${CMAKE_CURRENT_SOURCE_DIR}/../testdata")
add_test(NAME nodelock COMMAND test_nodelock)
