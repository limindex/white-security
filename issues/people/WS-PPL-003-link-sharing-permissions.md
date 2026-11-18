---
id: WS-PPL-003
title: Link Sharing Permissions (공유 링크·문서 권한)
slug: link-sharing-permissions
group: people
severity: medium
site_only: true
depth: brief
languages: []
maps:
  owasp2025: []
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: []
  cwe: []
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Google Drive·OneDrive·SharePoint 같은 클라우드 문서, 노션·디자인·설문 도구의 공개 링크, 고객 명단·견적·계약서·API 키가 든 문서]
severity_reason: "영향: 링크를 아는 사람이면 누구나 고객 명단·계약서·키를 열람, 편집 권한이면 내용 변조까지 · 악용: 메일 전달·채팅 붙여넣기·검색 노출로 링크가 퍼짐"
sources:
  - title: "Google Drive Help - Share and manage access to files and folders in Google Drive (General access, Add an expiration date)"
    url: https://support.google.com/drive/answer/2494822
    year: 2026
  - title: "Microsoft Support - Share OneDrive files and folders (Anyone links, expiration, password)"
    url: https://support.microsoft.com/en-us/office/share-onedrive-files-and-folders-9fcc2f7d-de0c-4cec-93b0-a82024800c07
    year: 2026
---

## 요약

문서를 "링크가 있는 모든 사용자"(Anyone with the link)로 공유하거나 폴더째 편집 권한을 준 채 잊어, 전달된 링크를 받은 사람 누구나 고객 명단·계약서·API 키를 열거나 고칠 수 있는 문제. Google Drive는 이 설정이면 로그인 없이도 열 수 있고, OneDrive의 Anyone 링크는 다시 전달받은 사람까지 접근할 수 있으므로(각 도움말), 필요한 사람만 지정하고 기한을 걸어야 함.

## 발생 상황

- 외부 협력사에 보낸 "링크가 있는 모든 사용자" 링크가 메일·채팅으로 계속 전달됨
- 폴더에 편집 권한을 주어 그 안의 모든 파일까지 같은 권한이 퍼짐(폴더 권한 상속)
- 프로젝트가 끝난 뒤에도 퇴사자·외부인 권한과 공개 링크가 그대로 남음

## 대응 방법

- 공유는 사람(메일 주소)을 지정해서 하고, 링크 공유는 "제한됨"이나 "조직 내 사용자"를 기본으로 둠.
- 외부 공유에는 기한을 걸고, 필요하면 보기 전용·다운로드 금지로 설정. Google Drive는 직장·학교 계정에서 사람별 공유에 만료일을 걸 수 있고, OneDrive는 따로 만든 공유 링크에 만료일·암호를 걸 수 있음(각 도움말).
- 개인정보·키가 든 문서는 공유 폴더 밖 별도 폴더로 옮겨 권한을 따로 관리.
- 분기마다 "공개" "링크가 있는 모든 사용자" 문서를 검색해 정리하고, 키가 든 문서가 공개됐다면 키부터 폐기.

## 점검 항목

- 개인정보·계약서·키가 든 문서는 "링크가 있는 모든 사용자" 공유가 아니어야 한다.
- 외부 공유에는 만료일과 최소 권한(보기·댓글)을 걸어야 한다.
- 폴더를 공유할 때는 안의 파일이 같은 권한을 받는다는 점을 확인해야 한다.
- 프로젝트 종료·퇴사 때 외부인 권한과 공개 링크를 거둬야 한다.
- API 키·비밀번호는 문서에 적지 않고 비밀값 관리 도구에 두어야 한다(WS-SEC-001).
