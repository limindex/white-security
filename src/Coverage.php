<?php
class CoverageError extends RuntimeException
{
    /** @param list<string> $problems 파일·키·항목 한 줄씩 */
    public function __construct(public readonly array $problems)
    {
        parent::__construct('존재하지 않는 표준 항목 ' . count($problems) . '건: ' . implode(' / ', $problems));
    }
}

/** 표준(프레임워크) 항목 ↔ 이슈 대응표. */
final class Coverage
{
    private static ?array $nameCache = null;
    private static ?array $aboutCache = null;

    /**
     * data/frameworks/*.json 을 Issue::MAP_KEYS 순서로 읽는다. 문제는 $errors 에 남기고 그 파일만 뺀다.
     * @param list<string> $errors @return list<array<string,mixed>>
     */
    public static function loadFrameworks(string $root, array &$errors): array
    {
        $dir = rtrim($root, '/') . '/data/frameworks';
        $found = [];
        $files = is_dir($dir) ? glob($dir . '/*.json') : [];
        if (!$files) { $errors[] = 'data/frameworks: 표준 항목 파일이 없습니다'; return []; }
        sort($files);
        foreach ($files as $f) {
            $name = 'data/frameworks/' . basename($f);
            $fw = json_decode((string)@file_get_contents($f), true);
            $why = self::checkFramework($fw);
            if ($why !== null) { $errors[] = "$name: $why"; continue; }
            if (isset($found[$fw['id']])) { $errors[] = "$name: id `{$fw['id']}` 가 다른 파일과 겹칩니다"; continue; }
            $found[$fw['id']] = $fw;
        }
        $out = [];
        foreach (Issue::MAP_KEYS as $k) if (isset($found[$k])) { $out[] = $found[$k]; unset($found[$k]); }
        foreach ($found as $fw) $out[] = $fw;
        return $out;
    }

    private static function checkFramework(mixed $fw): ?string
    {
        if (!is_array($fw)) return 'JSON 형식이 아닙니다';
        foreach (['id', 'name', 'url'] as $k) if (!isset($fw[$k]) || !is_string($fw[$k]) || trim($fw[$k]) === '') return "`$k` 가 없습니다";
        if (!isset($fw['year']) || !is_int($fw['year'])) return '`year` 가 없습니다';
        if (!preg_match('#^https://\S+$#', $fw['url'])) return '`url` 은 https:// 로 시작해야 합니다';
        if (isset($fw['about']) && (!is_string($fw['about']) || trim($fw['about']) === '' || mb_strlen($fw['about']) > 140)) return '`about` 은 140자 이하 한 줄 문자열이어야 합니다';
        if (!isset($fw['items']) || !is_array($fw['items']) || !array_is_list($fw['items']) || !$fw['items']) return '`items` 목록이 없습니다';
        $ids = [];
        foreach ($fw['items'] as $n => $it) {
            if (!is_array($it) || !isset($it['id'], $it['title'], $it['title_ko']) || !is_string($it['id']) || $it['id'] === '' || !is_string($it['title']) || !is_string($it['title_ko'])) return 'items[' . ($n + 1) . '] 에 id·title·title_ko 가 없습니다';
            if (isset($ids[$it['id']])) return "항목 id `{$it['id']}` 가 두 번 나옵니다";
            $ids[$it['id']] = true;
        }
        return null;
    }

    /**
     * 항목마다 이슈를 모은다. 이슈가 가리키는 항목이 표준에 없으면 모아서 CoverageError.
     * @param array<int,array<string,mixed>> $frameworks
     * @return array<string,list<array{item:string,title:string,title_ko:string,issues:list<string>,site_only:bool}>>
     */
    public static function build(Repository $r, array $frameworks, bool $strict = true): array
    {
        $byId = [];
        foreach ($frameworks as $fw) $byId[$fw['id']] = $fw;
        $rows = [];
        foreach ($byId as $id => $fw) foreach ($fw['items'] as $it) $rows[$id][$it['id']] = ['item' => $it['id'], 'title' => $it['title'], 'title_ko' => $it['title_ko'], 'issues' => [], 'site_only' => false, 'verified' => ($it['verified'] ?? true) !== false];

        $problems = [];
        $siteOnly = []; // fw => item => 이슈가 전부 site_only 인가
        foreach ($r->all() as $issue) {
            $file = basename($issue->path);
            foreach ($issue->maps as $key => $codes) {
                foreach ($codes as $code) {
                    if (!isset($byId[$key])) { $problems[] = "$file: maps.$key — `$key` 프레임워크 파일이 없습니다"; break; }
                    $item = $code;
                    $sep = $byId[$key]['rollup'] ?? null;
                    if (!isset($rows[$key][$item]) && is_string($sep) && $sep !== '' && ($p = strpos($code, $sep)) !== false) $item = substr($code, 0, $p);
                    if (!isset($rows[$key][$item]) && $key === 'cwe' && preg_match(Issue::CWE_ID, $code)) continue; // Top 25 밖 CWE: 유효, 표에는 없음
                    if (!isset($rows[$key][$item])) { $problems[] = "$file: maps.$key — `$code` 항목이 없습니다"; continue; }
                    if (!in_array($issue->id, $rows[$key][$item]['issues'], true)) {
                        $rows[$key][$item]['issues'][] = $issue->id;
                        $siteOnly[$key][$item][] = $issue->siteOnly;
                    }
                }
            }
        }
        if ($problems && $strict) throw new CoverageError(array_values(array_unique($problems)));

        $out = [];
        foreach ($rows as $key => $items) {
            foreach ($items as $item => $row) {
                $flags = $siteOnly[$key][$item] ?? [];
                $row['site_only'] = $flags !== [] && !in_array(false, $flags, true);
                $out[$key][] = $row;
            }
        }
        return $out;
    }

    /** 이슈가 하나도 없는 항목. @param array<string,list<array<string,mixed>>> $built @return list<array{fw:string,item:string}> */
    public static function gaps(array $built): array
    {
        $out = [];
        foreach ($built as $fw => $rows) foreach ($rows as $row) if (!$row['issues']) $out[] = ['fw' => $fw, 'item' => $row['item']];
        return $out;
    }

    /** 맵 키 → 화면 이름(short ?? name). 프로젝트의 data/frameworks 가 유일한 출처. @return array<string,string> */
    public static function names(): array
    {
        if (self::$nameCache === null) {
            $errs = [];
            self::$nameCache = [];
            foreach (self::loadFrameworks(__DIR__ . '/..', $errs) as $fw) self::$nameCache[$fw['id']] = (string)($fw['short'] ?? $fw['name']);
        }
        return self::$nameCache;
    }

    /** 맵 키 → 기준 한 줄 풀이(about). 없으면 키가 빠짐. @return array<string,string> */
    public static function abouts(): array
    {
        if (self::$aboutCache === null) {
            $errs = [];
            self::$aboutCache = [];
            foreach (self::loadFrameworks(__DIR__ . '/..', $errs) as $fw) if (isset($fw['about'])) self::$aboutCache[$fw['id']] = trim($fw['about']);
        }
        return self::$aboutCache;
    }
}
