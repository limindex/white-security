<?php
/**
 * 스킬 보고 예시(설치·심각도 기준 공용). 형식은 스킬 보고(SKILL.md 의 Report 절)의 표와 같다:
 * 등급 · 문서 번호 · 국내 기준 · 파일:줄 · 영향 · 이유 · 고칠 방법(참고는 스킬에서 한 줄 목록, 예시는 표로 통일).
 * 행의 등급은 각 이슈 탐지 규칙의 등급 줄과 맞춘 예.
 * @var Site $site
 */
$rows = [
    ['high', 'WS-INJ-001', '행안부 입력-1 · 주통기 SI', 'app/search.py:18', 'DB 전체 조회·삭제 가능', '검색어 q가 f-string으로 SQL에 들어감', '매개변수 바인딩으로 교체'],
    ['medium', 'WS-SEC-001', '행안부 보안-6 · 주통기 IL', 'app/config.py:7', '세션 위조 가능', '환경 변수가 없으면 고정 비밀키로 동작', '기본값을 지우고 값이 없으면 시작 단계에서 오류'],
    ['low', 'WS-XSS-001', '행안부 입력-4 · 주통기 XS', 'app/main.py:30', 'XSS 피해 확대', '응답에 CSP 헤더 없음', 'CSP 헤더 추가'],
];
?>
<div class="ws-table-wrap">
<table class="ws-table ws-table--report">
<caption>보고 예시</caption>
<thead><tr><th scope="col">등급</th><th scope="col">문서 번호</th><th scope="col">국내 기준</th><th scope="col">파일:줄</th><th scope="col">영향</th><th scope="col">이유</th><th scope="col">고칠 방법</th></tr></thead>
<tbody>
<?php foreach ($rows as [$sev, $id, $kr, $loc, $impact, $why, $fix]): ?>
<tr><td><?= Site::severityBadge($sev) ?></td><td><span class="ws-id"><?= Site::e($id) ?></span></td><td data-label="국내 기준"><?= Site::t($kr) ?></td><td><code><?= Site::e($loc) ?></code></td><td data-label="영향"><?= Site::t($impact) ?></td><td data-label="이유"><?= Site::t($why) ?></td><td data-label="고칠 방법"><?= Site::t($fix) ?></td></tr>
<?php endforeach ?>
</tbody>
</table>
</div>
