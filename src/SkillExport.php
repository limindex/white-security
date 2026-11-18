<?php
/** issues/ 원본에서 스킬 참고 파일(references/)을 만든다. site_only 이슈·묶음은 뺀다. */
final class SkillExport
{
    public const HEADER = "<!-- 생성 파일: issues/ 원본을 고치세요 -->\n";
    private const MAP_LABELS = [
        'owasp2025' => 'OWASP', 'api2023' => 'API', 'llm2026' => 'LLM', 'agentic2026' => '에이전트',
        'asvs5' => 'ASVS', 'cwe' => '', 'kr_guide' => '진단가이드', 'kr_ciip' => '기반시설',
    ];
    /** 스킬 파일에 옮기는 절 (이 순서) */
    private const SECTIONS = ['체크 항목', '스킬이 잡는 방식', '코드'];

    /** @return list<string> 쓴 파일(상대 경로, 정렬됨) */
    public static function write(Repository $repo, string $dir): array
    {
        $dir = rtrim($dir, '/');
        $written = [];
        $put = function (string $rel, string $text) use ($dir, &$written, &$keep): void {
            $path = $dir . '/' . $rel;
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
            file_put_contents($path, $text);
            $written[] = $rel;
            $keep[$rel] = true;
        };
        $siteOnlyGroups = [];
        $keep = [];
        foreach ($repo->groups() as $g) if ($g['site_only']) $siteOnlyGroups[$g['id']] = true;

        $index = self::HEADER . "# 화이트 보안 — 이슈 목록\n\n"
            . "아래에서 지금 하는 일과 맞는 묶음을 고르고, 해당 이슈 파일만 읽는다.\n";
        $by = $repo->byGroup();
        foreach ($repo->groups() as $g) {
            if (isset($siteOnlyGroups[$g['id']])) continue;
            $list = array_values(array_filter($by[$g['id']] ?? [], fn(Issue $i) => !$i->siteOnly));
            if (!$list) continue;
            $when = $g['when'] ?? null;
            if ($when === null || $when === '') $when = "「{$g['name']}」 상황에서 코드를 쓰거나 고칠 때";
            $index .= "\n## {$g['name']}\n\n언제 읽나: $when\n\n";
            foreach ($list as $i) {
                $index .= "- [{$i->id} {$i->title}]({$i->group}/{$i->id}.md) — " . self::summary($i) . "\n";
                $put($i->group . '/' . $i->id . '.md', self::issueFile($i));
            }
        }
        $put('INDEX.md', $index);
        self::prune($dir, $keep);
        sort($written);
        return $written;
    }

    /** 이번에 만들지 않은 생성 파일(머리 주석이 있는 .md)만 지우고, 빈 묶음 폴더를 치운다. */
    private static function prune(string $dir, array $keep): void
    {
        if (!is_dir($dir)) return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $rel = substr($f->getPathname(), strlen($dir) + 1);
            if ($f->isLink()) continue;
            if ($f->isDir()) { @rmdir($f->getPathname()); continue; } // 비어 있을 때만 지워진다
            if (strtolower($f->getExtension()) !== 'md' || isset($keep[$rel])) continue;
            $h = fopen($f->getPathname(), 'r');
            $first = $h ? fgets($h) : false;
            if ($h) fclose($h);
            if ($first === rtrim(self::HEADER, "\n") . "\n") unlink($f->getPathname());
        }
    }

    private static function summary(Issue $i): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $i->sections['한 줄 요약'] ?? ''));
        return $s !== '' ? $s : $i->title;
    }

    private static function issueFile(Issue $i): string
    {
        $parts = [Site::SEVERITY_LABELS[$i->severity] ?? $i->severity];
        foreach ($i->maps as $key => $codes) {
            $label = self::MAP_LABELS[$key] ?? $key;
            foreach ($codes as $c) $parts[] = trim($label . ' ' . $c);
        }
        $out = self::HEADER . "# {$i->id} {$i->title}\n\n" . implode(' · ', $parts) . "\n";
        foreach (self::SECTIONS as $h) {
            if (isset($i->sections[$h]) && trim($i->sections[$h]) !== '') $out .= "\n## $h\n\n" . $i->sections[$h] . "\n";
        }
        return $out;
    }
}
