<?php
/** 본문 마크다운 → HTML. 원본 HTML 은 통과시키지 않는다. */
final class Markdown
{
    public static function render(string $md): string
    {
        $p = new Parsedown();
        $p->setSafeMode(true);
        $p->setMarkupEscaped(true);
        $html = $p->text($md);
        // 코드 블록 언어 클래스는 lang-<x> 로 쓴다 (코드 내용은 이미 이스케이프됨).
        return Site::keep(str_replace('<code class="language-', '<code class="lang-', $html));
    }
}
