---
title: "CI 러너는 컨테이너일 수도, 호스트에서 직접 돌 수도 있어 DB 접속 방식을 둘 다 처리한다"
author: Byunjongwon
date: 2026-11-25
branch: feature/ci-runner-hostname-fallback
cost: { minutes: 4, tokens: 108535 }
id: l-4bb7cea0
scope: repo
status: active
src: agent
touches:
  - ".github/workflows/ci.yml"
---

## Decided
러너가 컨테이너면 CI 전용 도커 네트워크에 붙여 컨테이너 이름으로 접속한다. 호스트에서 직접 도는 러너면 MySQL 컨테이너의 브리지 IP로 접속한다.

## Tried and ruled out
러너가 항상 컨테이너라고 가정하고 `docker network connect ... "$(hostname)"`만 쓰는 방식. runner-02/03은 Machine name이 호스트(martin-server)라 "No such container"로 즉시 실패했다.

## Watch out
같은 `docker` 라벨이어도 러너마다 컨테이너 여부가 다르다. 새 CI 단계를 만들 때 hostname이 컨테이너 이름이라고 가정하지 않는다.

In the code:
- .github/workflows/ci.yml 의 backend·frontapi "MySQL 컨테이너 기동" 스텝: `docker inspect "$(hostname)"`로 분기, 호스트 경로는 `docker inspect -f '{{(index .NetworkSettings.Networks "<net>").IPAddress}}'`
