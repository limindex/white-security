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
     * 화면용 탐지 규칙 마크다운: 원본은 칸마다 한 줄(Issue::parseDetection)이지만, 화면에서는 검사의 지적할 때/지적하지 않을 때와
     * 등급의 위험/주의/참고를 하위 목록으로 펼쳐 읽기 쉽게 한다. 내용 문자열은 그대로.
     * @param array{where:string,checks:list<array{name:string,flag:string,skip:string}>,grade:array<string,string>,fields:array<string,string>} $d
     */
    public static function detectionMarkdown(array $d): string
    {
        $out = ['- **찾는 곳**: ' . $d['where']];
        foreach ($d['checks'] as $n => $c) {
            $out[] = '- **검사 ' . ($n + 1) . ' — ' . $c['name'] . '**';
            $out[] = '  - 지적할 때: ' . $c['flag'];
            $out[] = '  - 지적하지 않을 때: ' . $c['skip'];
        }
        $out[] = '- **등급**';
        foreach ($d['grade'] as $lv => $t) $out[] = "  - $lv: $t";
        foreach (Issue::DETECTION_FIELDS as $f) if (isset($d['fields'][$f])) $out[] = "- **$f**: " . $d['fields'][$f];
        return implode("\n", $out) . "\n";
    }

    /** 탐지 규칙의 보고 형식·보고 예(한 줄 코드)는 긴 칩 대신 블록 줄로(공백에서 줄바꿈). 안쪽도 줄머리 금칙(Site::keep) + [등급]·<칸> 토큰 묶음 */
    public static function reportLines(string $html): string
    {
        return (string)preg_replace_callback('#(<strong>보고 (?:형식|예)</strong>:) <code(?: class="ws-code-long")?>(.*?)</code>(?=</li>)#u',
            fn(array $m): string => $m[1] . '<code class="ws-code-line">' . preg_replace('#(\[[^\]<]*\]|&lt;[^\s<]*?&gt;|&lt;[^<]*?&gt;(?=\s|$))#u', '<span class="ws-nw">$1</span>', Site::keep($m[2])) . '</code>', $html);
    }

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
        if ($kind === 'prompt') $html = (string)preg_replace_callback('#<pre><code([^>]*)>(.*?)</code></pre>#s', fn(array $m) => '<pre><code' . $m[1] . '>' . self::promptLines($m[2]) . '</code></pre>', $html);
        return str_replace(['<pre>', '</pre>'], [$head, '</pre></figure>'], $html);
    }

    /**
     * 프롬프트(한국어 문장) 줄마다 블록 span: 줄마다 고르게 접히고 줄머리 금칙 적용.
     * 줄 사이 "\n" 은 그대로 두어 복사하면 원래 줄바꿈이 남는다.
     */
    public static function promptLines(string $escaped): string
    {
        $lines = explode("\n", rtrim($escaped, "\n"));
        return implode("\n", array_map(fn(string $l) => trim($l) === '' ? '<span class="ws-pl ws-pl--gap"></span>' : '<span class="ws-pl">' . Site::keep($l) . '</span>', $lines));
    }
}
