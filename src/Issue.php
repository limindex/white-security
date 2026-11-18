<?php
class IssueError extends RuntimeException {}

/** 이슈 원본 파일 하나. 검증을 통과한 값만 들고 있다. */
final class Issue
{
    public const SEVERITIES = ['high', 'medium', 'low'];
    public const DEPTHS = ['full', 'brief'];
    public const MAP_KEYS = ['owasp2025', 'api2023', 'llm2026', 'agentic2026', 'asvs5', 'cwe', 'kr_guide', 'kr_ciip'];
    public const FULL_SECTIONS = ['요약', '발생 상황', '위험 배경', '대응 방법', '점검 항목', '탐지 규칙', '코드 예시'];
    public const BRIEF_SECTIONS = ['요약', '점검 항목', '코드 예시'];
    /** maps.cwe 는 Top 25 밖 번호도 받는다. 커버리지 표는 data/frameworks/cwe.json(Top 25)만 센다. */
    public const CWE_ID = '/^CWE-\d+$/';
    /**
     * `## 탐지 규칙` 칸 이름(이 순서). 한 칸 = 한 줄 `- **이름**: 내용`. 찾는 곳·등급은 꼭 1개, 검사는 1개 이상(`검사 N — 이름`),
     * 나머지는 0~1개. 보고 형식 줄은 사이트에만 보이고 스킬 참고 파일에는 옮기지 않는다(SkillExport).
     */
    public const DETECTION_FIELDS = ['찾는 곳', '검사', '등급', '보고 형식', '보고 예', '검토 주의', '고칠 방법'];
    public const GRADE_LEVELS = ['위험', '주의', '참고'];

    /**
     * @param array<string,string> $sections
     * @param array<string,array{bad:string,good:string}> $code
     * @param list<array{date:string,note:string}> $revisions 개정 이력, 최신이 위
     * @param list<string> $appliesTo 적용 대상(언어·프레임워크·환경)
     */
    private function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $group,
        public readonly string $severity,
        public readonly bool $siteOnly,
        public readonly string $depth,
        public readonly array $languages,
        public readonly array $maps,
        public readonly DateTimeImmutable $checked,
        public readonly array $sources,
        public readonly string $body,
        public readonly array $sections,
        public readonly array $code,
        public readonly string $path,
        public readonly DateTimeImmutable $published,
        public readonly array $revisions,
        public readonly array $appliesTo,
        public readonly ?string $severityReason,
        /** @var array{where:string,signals:list<string>,checks:list<array{name:string,flag:string,skip:string}>,grade:array<string,string>,fields:array<string,string>}|null site_only 이슈는 null */
        public readonly ?array $detection = null,
    ) {}

    /** @param array<int,array{id:string}> $groups data/groups.json 내용 */
    public static function fromFile(string $path, array $groups): Issue
    {
        $name = basename($path);
        $fail = fn(string $why) => new IssueError("$name: $why");
        $raw = @file_get_contents($path);
        if ($raw === false) throw $fail('파일을 읽을 수 없습니다');
        try {
            [$fm, $body] = FrontMatter::split($raw);
        } catch (FrontMatterError $e) {
            throw $fail('머리말 오류 - ' . $e->getMessage());
        }

        $str = function (string $key) use ($fm, $fail): string {
            if (!isset($fm[$key]) || !is_string($fm[$key]) || trim($fm[$key]) === '') throw $fail("`$key` 가 없거나 문자열이 아닙니다");
            return $fm[$key];
        };

        $id = $str('id');
        if (!preg_match('/^WS-[A-Z]{2,5}-\d{3}$/', $id)) throw $fail("id `$id` 형식이 WS-XXX-000 이 아닙니다");
        $title = $str('title');
        $slug = $str('slug');
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) throw $fail("slug `$slug` 는 소문자·숫자·하이픈만 쓸 수 있습니다");

        $group = $str('group');
        $groupIds = array_column($groups, 'id');
        if (!in_array($group, $groupIds, true)) throw $fail("group `$group` 이(가) groups.json 에 없습니다");

        $severity = $str('severity');
        if (!in_array($severity, self::SEVERITIES, true)) throw $fail("severity `$severity` 는 high|medium|low 중 하나여야 합니다");
        $depth = $str('depth');
        if (!in_array($depth, self::DEPTHS, true)) throw $fail("depth `$depth` 는 full|brief 중 하나여야 합니다");

        $siteOnly = $fm['site_only'] ?? false;
        if (!is_bool($siteOnly)) throw $fail('site_only 는 true|false 여야 합니다');

        $languages = $fm['languages'] ?? [];
        if (!is_array($languages) || array_filter($languages, fn($l) => !is_string($l) || $l === '')) throw $fail('languages 는 문자열 목록이어야 합니다');
        if (count(array_unique($languages)) !== count($languages)) throw $fail('languages 에 중복이 있습니다');
        if (!$siteOnly && !$languages) throw $fail('languages 는 site_only: true 가 아니면 1개 이상이어야 합니다');

        $mapsIn = $fm['maps'] ?? [];
        if ($mapsIn === null) $mapsIn = [];
        if (!is_array($mapsIn) || ($mapsIn && array_is_list($mapsIn))) throw $fail('maps 는 키: 목록 형태여야 합니다');
        $maps = array_fill_keys(self::MAP_KEYS, []);
        foreach ($mapsIn as $k => $v) {
            if (!in_array($k, self::MAP_KEYS, true)) throw $fail("maps 에 모르는 키 `$k` 가 있습니다");
            if ($v === null) $v = [];
            if (!is_array($v) || !array_is_list($v)) throw $fail("maps.$k 는 목록이어야 합니다");
            foreach ($v as $item) if (!is_string($item) || $item === '') throw $fail("maps.$k 항목은 문자열이어야 합니다");
            if ($k === 'cwe') foreach ($v as $item) if (!preg_match(self::CWE_ID, $item)) throw $fail("maps.cwe 항목 `$item` 은(는) CWE-숫자 형식이어야 합니다");
            $maps[$k] = $v;
        }

        $date = function (mixed $raw, string $what) use ($fail): DateTimeImmutable {
            if (!is_string($raw) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) throw $fail("$what 는 YYYY-MM-DD 여야 합니다");
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
            if (!$d || $d->format('Y-m-d') !== $raw) throw $fail("$what `$raw` 는 달력에 없는 날짜입니다");
            return $d;
        };
        $checked = $date($fm['checked'] ?? null, 'checked');

        // 문서 메타(선택): 최초 게시일 · 개정 이력 · 적용 대상 · 심각도 근거
        $published = isset($fm['published']) ? $date($fm['published'], 'published') : $checked;
        if ($published > $checked) throw $fail('published 는 checked 보다 늦을 수 없습니다');

        $revIn = $fm['revisions'] ?? [];
        if ($revIn === null) $revIn = [];
        if (!is_array($revIn) || !array_is_list($revIn)) throw $fail('revisions 는 date/note 목록이어야 합니다');
        $revisions = [];
        $prev = null;
        foreach ($revIn as $n => $r) {
            $n1 = $n + 1;
            if (!is_array($r) || array_is_list($r)) throw $fail("revisions[$n1] 는 date/note 맵이어야 합니다");
            $d = $date($r['date'] ?? null, "revisions[$n1].date");
            $note = $r['note'] ?? null;
            if (!is_string($note) || trim($note) === '' || preg_match('/[\r\n]/', $note)) throw $fail("revisions[$n1].note 는 한 줄 문자열이어야 합니다");
            if ($prev !== null && $d > $prev) throw $fail("revisions 는 최신 날짜가 위에 오도록 적어야 합니다 (revisions[$n1])");
            if ($d < $published) throw $fail("revisions[$n1].date 가 published 보다 이릅니다");
            $prev = $d;
            $revisions[] = ['date' => $d->format('Y-m-d'), 'note' => trim($note)];
        }

        $appliesTo = $fm['applies_to'] ?? [];
        if ($appliesTo === null) $appliesTo = [];
        if (!is_array($appliesTo) || !array_is_list($appliesTo) || array_filter($appliesTo, fn($a) => !is_string($a) || trim($a) === '')) throw $fail('applies_to 는 문자열 목록이어야 합니다');
        if (count(array_unique($appliesTo)) !== count($appliesTo)) throw $fail('applies_to 에 중복이 있습니다');

        $severityReason = $fm['severity_reason'] ?? null;
        if ($severityReason !== null && (!is_string($severityReason) || trim($severityReason) === '' || preg_match('/[\r\n]/', $severityReason))) throw $fail('severity_reason 은 한 줄 문자열이어야 합니다');
        if ($severityReason !== null) $severityReason = trim($severityReason);

        $sourcesIn = $fm['sources'] ?? [];
        if ($sourcesIn === null) $sourcesIn = [];
        if (!is_array($sourcesIn) || !array_is_list($sourcesIn)) throw $fail('sources 는 목록이어야 합니다');
        if (!$sourcesIn) throw $fail('sources 는 1개 이상이어야 합니다');
        $sources = [];
        foreach ($sourcesIn as $n => $s) {
            $n1 = $n + 1;
            if (!is_array($s)) throw $fail("sources[$n1] 는 title/url/year 맵이어야 합니다");
            if (!isset($s['title']) || !is_string($s['title']) || trim($s['title']) === '') throw $fail("sources[$n1].title 이 없습니다");
            if (!isset($s['url']) || !is_string($s['url']) || !preg_match('#^https://[^\s]+$#', $s['url'])) throw $fail("sources[$n1].url 은 https:// 로 시작해야 합니다");
            if (!isset($s['year']) || !is_int($s['year']) || $s['year'] < 1990 || $s['year'] > 2100) throw $fail("sources[$n1].year 는 연도(정수)여야 합니다");
            $sources[] = ['title' => $s['title'], 'url' => $s['url'], 'year' => $s['year']];
        }

        $sections = self::sections($body);
        $required = $depth === 'full' ? self::FULL_SECTIONS : self::BRIEF_SECTIONS;
        foreach ($required as $h) {
            if ($siteOnly && $h === '코드 예시') continue; // 사람 영역 이슈는 코드 예시 절이 없어도 된다
            if (!isset($sections[$h])) throw $fail("필수 제목 `## $h` 가 없습니다 (depth: $depth)");
        }
        try {
            $code = self::extractCode($sections['코드 예시'] ?? '');
        } catch (IssueError $e) {
            throw $fail($e->getMessage());
        }
        $detection = null;
        if (!$siteOnly) {
            if (!isset($sections['탐지 규칙'])) throw $fail('필수 제목 `## 탐지 규칙` 가 없습니다 (site_only 가 아니면 depth 와 관계없이 필요)');
            try {
                $detection = self::parseDetection($sections['탐지 규칙']);
            } catch (IssueError $e) {
                throw $fail('탐지 규칙 - ' . $e->getMessage());
            }
        }
        $found = array_keys($code);
        $a = $languages; $b = $found; sort($a); sort($b);
        if ($a !== $b) throw $fail('languages [' . implode(', ', $languages) . '] 와 `## 코드 예시` 의 ### 소제목 [' . implode(', ', $found) . '] 이(가) 다릅니다');

        return new Issue($id, $title, $slug, $group, $severity, $siteOnly, $depth, $languages, $maps, $checked, $sources, $body, $sections, $code, $path, $published, $revisions, array_values($appliesTo), $severityReason, $detection);
    }

    /**
     * `## 탐지 규칙` 본문을 칸별로 읽는다. 형식이 하나라도 어긋나면 IssueError(무엇이 왜 틀렸는지).
     * @return array{where:string,signals:list<string>,checks:list<array{name:string,flag:string,skip:string}>,grade:array<string,string>,fields:array<string,string>}
     */
    public static function parseDetection(string $section): array
    {
        $rank = array_flip(self::DETECTION_FIELDS);
        $where = null; $checks = []; $grade = null; $fields = []; $last = -1;
        $form = '`- **칸 이름**: 내용` 한 줄 형식이 아닙니다(칸: ' . implode('·', self::DETECTION_FIELDS) . ')';
        foreach (explode("\n", $section) as $n => $line) {
            if (trim($line) === '') continue;
            $no = $n + 1;
            if (!preg_match('/^- \*\*(.+?)\*\*: (\S.*)$/u', $line, $m)) throw new IssueError("{$no}번째 줄이 $form");
            [$label, $text] = [$m[1], trim($m[2])];
            $field = preg_match('/^검사 (\d+) — (\S.*)$/u', $label, $c) ? '검사' : $label;
            if (!isset($rank[$field])) throw new IssueError("{$no}번째 줄의 `$label` 은(는) 모르는 칸입니다(칸: " . implode('·', self::DETECTION_FIELDS) . ', 검사는 `검사 N — 이름`)');
            if ($rank[$field] < $last) throw new IssueError("{$no}번째 줄 `$label` 의 순서가 틀립니다(" . implode(' → ', self::DETECTION_FIELDS) . ')');
            if ($field !== '검사' && $rank[$field] === $last) throw new IssueError("`$label` 칸이 두 번 나옵니다");
            $last = $rank[$field];
            if ($field === '찾는 곳') {
                $where = $text;
            } elseif ($field === '검사') {
                if ((int)$c[1] !== count($checks) + 1) throw new IssueError("`$label` 의 번호가 " . (count($checks) + 1) . ' 이어야 합니다');
                $parts = explode(' / 지적하지 않을 때: ', $text);
                if (count($parts) !== 2 || !str_starts_with($parts[0], '지적할 때: ') || trim($parts[1]) === '' || trim(substr($parts[0], strlen('지적할 때: '))) === '') {
                    throw new IssueError("`$label` 은(는) `지적할 때: … / 지적하지 않을 때: …` 형식이어야 합니다");
                }
                $checks[] = ['name' => trim($c[2]), 'flag' => trim(substr($parts[0], strlen('지적할 때: '))), 'skip' => trim($parts[1])];
            } elseif ($field === '등급') {
                $grade = [];
                $prev = -1;
                foreach (preg_split('/ \/ (?=(?:' . implode('|', self::GRADE_LEVELS) . '): )/u', $text) as $part) {
                    $lv = preg_match('/^(' . implode('|', self::GRADE_LEVELS) . '): (\S.*)$/u', $part, $g) ? array_search($g[1], self::GRADE_LEVELS, true) : false;
                    if ($lv === false || $lv <= $prev) throw new IssueError('`등급` 은(는) `위험: … / 주의: … / 참고: …` 형식(위험·주의·참고 순서, 각 0~1번)이어야 합니다');
                    $grade[$g[1]] = trim($g[2]);
                    $prev = $lv;
                }
            } else {
                $fields[$field] = $text;
            }
        }
        if ($where === null) throw new IssueError('`찾는 곳` 칸이 없습니다');
        if (!$checks) throw new IssueError('`검사 1 — 이름` 칸이 1개 이상 있어야 합니다');
        if ($grade === null) throw new IssueError('`등급` 칸이 없습니다');
        $signals = self::codeSpans($where);
        if (!$signals) throw new IssueError('`찾는 곳` 에 백틱으로 감싼 코드 신호(함수·패키지·파일 패턴)가 없습니다');
        return ['where' => $where, 'signals' => $signals, 'checks' => $checks, 'grade' => $grade, 'fields' => $fields];
    }

    /** 마크다운 인라인 코드(`a`, `` a`b ``)의 내용. 중복은 처음 것만. @return list<string> */
    public static function codeSpans(string $text): array
    {
        preg_match_all('/(?<!`)(`+)(?!`)(.+?)(?<!`)\1(?!`)/u', $text, $m);
        $out = [];
        foreach ($m[2] as $s) {
            if (strlen($s) > 2 && $s[0] === ' ' && $s[-1] === ' ') $s = substr($s, 1, -1);
            if (trim($s) !== '' && !in_array($s, $out, true)) $out[] = $s;
        }
        return $out;
    }

    /** 코드 펜스 안은 건너뛰고 `## ` 제목별로 본문을 자른다. @return array<string,string> */
    private static function sections(string $body): array
    {
        $out = []; $cur = null; $buf = []; $fence = null;
        foreach (explode("\n", $body) as $line) {
            if ($fence === null && preg_match('/^(`{3,}|~{3,})/', $line, $m)) $fence = $m[1];
            elseif ($fence !== null && preg_match('/^' . preg_quote($fence[0], '/') . '{' . strlen($fence) . ',}\s*$/', $line)) $fence = null;
            elseif ($fence === null && preg_match('/^## +(.+?)\s*$/', $line, $m)) {
                if ($cur !== null) $out[$cur] = trim(implode("\n", $buf), "\n");
                $cur = $m[1]; $buf = [];
                continue;
            }
            if ($cur !== null) $buf[] = $line;
        }
        if ($cur !== null) $out[$cur] = trim(implode("\n", $buf), "\n");
        return $out;
    }

    /** `### <lang>` 마다 ✗ 코드 블록, ✓ 코드 블록 한 쌍. @return array<string,array{bad:string,good:string}> */
    private static function extractCode(string $section): array
    {
        $out = []; $lang = null; $pending = null; $fence = null; $buf = [];
        foreach (explode("\n", $section) as $line) {
            if ($fence !== null) {
                if (preg_match('/^' . preg_quote($fence[0], '/') . '{' . strlen($fence) . ',}\s*$/', $line)) {
                    $out[$lang][$pending] = implode("\n", $buf);
                    $fence = null; $pending = null; $buf = [];
                } else $buf[] = $line;
                continue;
            }
            if (preg_match('/^### +(.+?)\s*$/', $line, $m)) {
                $lang = $m[1];
                if (isset($out[$lang])) throw new IssueError("`### $lang` 소제목이 중복됩니다");
                $out[$lang] = []; $pending = null;
            } elseif (preg_match('/^(`{3,}|~{3,})/', $line, $m)) {
                if ($lang === null) throw new IssueError('`### <언어>` 소제목 없이 코드 블록이 있습니다');
                if ($pending === null) throw new IssueError("$lang: ✗ / ✓ 표시 줄 없이 코드 블록이 있습니다");
                $fence = $m[1];
            } elseif ($lang !== null && preg_match('/^(✗|✓)/u', $line, $m)) {
                $pending = $m[1] === '✗' ? 'bad' : 'good';
                if (isset($out[$lang][$pending])) throw new IssueError("$lang: " . ($pending === 'bad' ? '✗' : '✓') . ' 코드 블록이 두 번 나옵니다');
            }
        }
        if ($fence !== null) throw new IssueError('코드 블록이 닫히지 않았습니다');
        foreach ($out as $l => $pair) {
            if (!isset($pair['bad'], $pair['good']) || trim($pair['bad']) === '' || trim($pair['good']) === '') {
                throw new IssueError("$l: ✗ 취약 코드와 ✓ 안전 코드 블록이 둘 다 있어야 합니다");
            }
        }
        return $out;
    }
}
