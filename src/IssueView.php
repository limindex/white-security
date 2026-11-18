<?php
/** 이슈 본문 HTML 을 화면용으로 다듬는다: 절 id · 접힘 여부 · 점검 체크 상자 · 프롬프트 카드. */
final class IssueView
{
    /** 고정 절 제목 → 영구 링크 id (주소에 한글이 들어가지 않게) */
    public const IDS = [
        '요약' => 'summary', '발생 상황' => 'situation', '위험 배경' => 'background', '대응 방법' => 'response',
        '점검 항목' => 'checklist', '탐지 규칙' => 'detection', '코드 예시' => 'code', '출처' => 'sources', '개정 이력' => 'revisions',
    ];
    /** 기본 접힘 절 */
    public const FOLDED = ['탐지 규칙', '출처', '개정 이력'];

    public static function sectionId(string $heading, int $n): string { return self::IDS[$heading] ?? 'sec-' . $n; }

    /**
     * 점검 항목 목록(<ul><li>)을 체크 상자 목록으로. 체크 상태는 브라우저가 data-check 키로 저장한다.
     * @return array{0:string,1:int} [HTML, 항목 수]
     */
    public static function checklist(string $html, string $issueId): array
    {
        $n = 0;
        $id = Site::e($issueId);
        $out = preg_replace_callback('#<ul>(.*?)</ul>#s', function (array $ul) use (&$n, $id): string {
            $items = preg_replace_callback('#<li>(.*?)</li>#s', function (array $li) use (&$n, $id): string {
                $n++;
                return '<li><input type="checkbox" id="chk-' . $id . '-' . $n . '"><label for="chk-' . $id . '-' . $n . '">' . trim($li[1]) . '</label></li>';
            }, $ul[1]);
            return '<ul class="ws-checklist" data-check="' . $id . '">' . $items . '</ul>';
        }, $html, 1);
        return [(string)$out, $n];
    }

    /** 본문 속 코드 블록을 복사 버튼 있는 카드로. $label 은 카드 이름(프롬프트·예시). */
    public static function blocks(string $html, string $label): string
    {
        $kind = $label === '프롬프트' ? 'prompt' : 'example';
        $head = '<figure class="ws-block ws-block--' . $kind . '"><figcaption><span class="ws-block-label">' . Site::e($label) . '</span>'
            . '<button type="button" class="ws-copy" data-copy aria-label="' . Site::e($label) . ' 복사"><span class="ws-tx">복사</span></button></figcaption><pre tabindex="0">';
        return str_replace(['<pre>', '</pre>'], [$head, '</pre></figure>'], $html);
    }
}
