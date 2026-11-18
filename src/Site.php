<?php
/** 경로 하나를 받아 {status,type,body} 를 돌려주는 라우터. live(서버)와 static(내보내기) 공용. */
final class Site
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    /** 일반 페이지: 주소 → 제목 · 메뉴 구역 · 한 줄 설명(명사형). nav=false 는 왼쪽 메뉴에서만 뺌. 왼쪽 메뉴·경로 표시·설명 메타가 모두 여기서 나온다. */
    public const PAGES = [
        'about' => ['title' => '소개', 'section' => '시작하기', 'lead' => 'AI와 함께 코딩하는 사람을 위한 한국어 보안 문서와 점검 스킬의 목적·범위·운영 원칙'],
        'start' => ['title' => '처음이라면', 'section' => '시작하기', 'lead' => '바이브 코딩을 시작한 사람이 가장 먼저 읽을 보안 이슈 10건과 읽는 순서'],
        'install' => ['title' => '스킬 설치', 'section' => '시작하기', 'lead' => '코딩 도구에 점검 스킬 설치 · 이후 코드 작성·검토 때 스킬의 자동 점검'],
        'check' => ['title' => '내 사이트 점검', 'section' => '시작하기', 'lead' => '사용 기술과 기능에 맞춘 점검표와 AI 점검 지시문'],
        'severity' => ['title' => '심각도 기준', 'section' => '시작하기', 'lead' => '위험·주의·참고 등급의 판정 기준 · 영향과 악용 쉬움의 조합'],
        'guide' => ['title' => '문서 읽는 법', 'section' => '시작하기', 'lead' => '문서 번호 체계·절 구성·문서 정보·기준 번호의 의미'],
        'glossary' => ['title' => '용어 사전', 'section' => '참고', 'lead' => '보안 업계 용어와 공식 용어의 쉬운 풀이·관련 이슈'],
        'coverage' => ['title' => '점검 기준 대응표', 'section' => '참고', 'nav' => false, 'lead' => '공인 보안 기준 8종의 항목별 대응 이슈와 빈칸'],
        'changelog' => ['title' => '개정 이력', 'section' => '참고', 'lead' => '스킬과 문서의 버전별 변경 내역'],
        'sources' => ['title' => '출처·라이선스', 'section' => '참고', 'lead' => '문서·코드·글꼴의 라이선스와 인용 원칙'],
    ];
    /** 왼쪽 메뉴 구역 순서. 보안 이슈 구역은 두 구역 사이에 들어간다. */
    public const NAV_SECTIONS = ['시작하기', '참고'];
    public const BRAND = 'White Security';
    /** 사이트 성격: 웹사이트는 가이드, 제품은 스킬 */
    public const BRAND_DESC = '바이브 코딩 보안 가이드';
    private const ID_RE = '/^WS-[A-Z]{2,5}-\d{3}$/';
    public const SEVERITY_LABELS = ['high' => '위험', 'medium' => '주의', 'low' => '참고'];
    public const LANG_LABELS = ['python' => 'Python', 'php' => 'PHP', 'js' => 'JavaScript', 'ts' => 'TypeScript', 'go' => 'Go', 'java' => 'Java', 'ruby' => 'Ruby', 'shell' => 'Shell', 'yaml' => 'YAML'];

    public readonly DateTimeImmutable $today;

    public function __construct(public readonly Repository $repo, private string $baseUrl, public readonly string $mode, ?DateTimeImmutable $today = null, private ?string $origin = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        $this->today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
    }

    public function url(string $path): string { return $this->baseUrl . ltrim($path, '/'); }

    /**
     * 요청 주소(REQUEST_URI)에서 base 뒤 경로만 뽑는다. 쿼리는 버리고 퍼센트 인코딩은 한 번만 푼다.
     * base 밖의 주소면 null.
     */
    public static function pathFromRequest(string $requestUri, string $base): ?string
    {
        $q = strpos($requestUri, '?');
        $uri = $q === false ? $requestUri : substr($requestUri, 0, $q);
        $base = '/' . trim($base, '/');
        if ($uri === $base) return '';
        if (!str_starts_with($uri, $base . '/')) return null;
        return rawurldecode(substr($uri, strlen($base) + 1));
    }

    /** 켜진 페이지 목록(data/site.json pages 반영): 왼쪽 메뉴·홈·정적 내보내기가 이것만 쓴다. @return array<string,array<string,string>> */
    public function pages(): array
    {
        return array_filter(self::PAGES, fn(string $slug) => $this->repo->pageOn($slug), ARRAY_FILTER_USE_KEY);
    }

    public function pageOn(string $slug): bool { return isset(self::PAGES[$slug]) && $this->repo->pageOn($slug); }

    /** 문서 메타(게시·확인·적용 대상·심각도 근거·개정 이력): 이슈 상세 JSON 용 @return array<string,mixed> */
    private function docMeta(Issue $i): array
    {
        return [
            'published' => $i->published->format('Y-m-d'), 'checked' => $i->checked->format('Y-m-d'),
            'applies_to' => $i->appliesTo,
            'severity_reason' => $i->severityReason, 'revisions' => $i->revisions,
        ];
    }

    public function groupName(string $id): string
    {
        foreach ($this->repo->groups() as $g) if ($g['id'] === $id) return $g['name'];
        return $id;
    }

    public static function langLabel(string $lang): string { return self::LANG_LABELS[$lang] ?? $lang; }

    /** 머리말 maps 키 → 화면 이름 (data/frameworks 가 출처, 모르는 키는 키 그대로) */
    public static function mapLabel(string $key): string { return Coverage::names()[$key] ?? $key; }

    /** CWE 번호면 MITRE 정의 쪽 주소, 아니면 null */
    public static function cweUrl(string $key, string $code): ?string
    {
        return $key === 'cwe' && preg_match('/^CWE-(\d+)$/', $code, $m) ? 'https://cwe.mitre.org/data/definitions/' . $m[1] . '.html' : null;
    }

    /** 기준 번호 칩 HTML: 기준 이름(번호에 이름이 없을 때) + 번호, CWE 는 정의 페이지 링크. 기준 한 줄 풀이(about)는 올리거나 포커스하면 나타남. */
    public static function mapChip(string $key, string $code): string
    {
        static $n = 0;
        $k = str_starts_with($code, strtoupper($key) . '-') ? '' : '<span class="ws-chip-k">' . self::e(self::mapLabel($key)) . '</span>';
        $u = self::cweUrl($key, $code);
        $about = Coverage::abouts()[$key] ?? '';
        $tip = $about !== '' ? 'ws-tip-' . (++$n) : '';
        $desc = $tip !== '' ? ' aria-describedby="' . $tip . '"' : '';
        $v = $u !== null
            ? '<a class="ws-chip-v" href="' . self::e($u) . '" rel="noopener noreferrer"' . $desc . '><span>' . self::e($code) . '</span>'
                . '<svg class="ws-ext" viewBox="0 0 12 12" width="10" height="10" aria-hidden="true" focusable="false"><path d="M4.5 2.5h5v5M9.5 2.5l-7 7" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a>'
            : '<span class="ws-chip-v">' . self::e($code) . '</span>';
        $focus = $u === null && $tip !== '' ? ' tabindex="0"' . $desc : '';
        return '<li class="ws-chip"' . $focus . '>' . $k . $v . ($tip !== '' ? '<span class="ws-tip" role="tooltip" id="' . $tip . '">' . self::e($about) . '</span>' : '') . '</li>';
    }

    /** 심각도 배지 HTML (위험 빨강 · 주의 주황 · 참고 회색) */
    public static function severityBadge(string $severity, string $suffix = ''): string
    {
        $label = self::SEVERITY_LABELS[$severity] ?? $severity;
        return '<span class="ws-badge ws-badge--' . self::e($severity) . '">' . self::e($label . $suffix) . '</span>';
    }

    public const HOME_DESC = '바이브 코딩 개발자 누구나 활용하는 공개 점검 스킬 · 국내외 보안 기준 반영';
    public const DEFAULT_DESC = 'AI 코딩 도구가 자주 만드는 보안 실수를 정리한 보안 이슈 문서와 AI 코딩 도구용 점검 스킬';

    /** data/site.json 설정값 (없으면 빈 문자열) */
    public function setting(string $key): string
    {
        if ($key === 'origin' && $this->origin !== null) return $this->origin;
        $v = $this->repo->settings()[$key] ?? '';
        return is_string($v) ? $v : '';
    }

    /** repository 공개 여부(data/site.json released) */
    public function released(): bool { return ($this->repo->settings()['released'] ?? false) === true; }

    /** 설치 command 에 쓰는 repository 이름(owner/repo): data/site.json 의 github 에서만. 없으면 빈 문자열(설치 command 숨김) */
    public function repoSlug(): string
    {
        return preg_match('#^https://github\.com/([A-Za-z0-9._-]+/[A-Za-z0-9._-]+)/?$#', $this->setting('github'), $m) ? $m[1] : '';
    }

    /** 본문 HTML 줄머리 금칙: 닫는 괄호·코드 뒤 조사, 가운뎃점이 줄 맨 앞에 오지 않게 nowrap 묶음(보이지 않는 문자 없음) */
    public static function keep(string $html): string
    {
        $parts = preg_split('#(<pre\b.*?</pre>)#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $p) {
            if ($i % 2) continue;
            $p = (string)preg_replace('#(<code>[^<]*</code>)([가-힣]{1,2})#u', '<span class="ws-nw">$1$2</span>', $p);
            $p = (string)preg_replace('#(<code>[^<]*</code>)( ?·)#u', '<span class="ws-nw">$1$2</span>', $p);
            $p = self::inText($p, '#(\S*\))([가-힣]{1,2})#u', '<span class="ws-nw">$1$2</span>');
            $p = self::inText($p, '#(\S+ ?·)#u', '<span class="ws-nw">$1</span>');
            $parts[$i] = $p;
        }
        return implode('', $parts);
    }

    /** 태그 밖·코드 밖 글자 조각에만 정규식 치환(앞 규칙이 넣은 태그는 건드리지 않음) */
    private static function inText(string $html, string $re, string $to): string
    {
        $seg = preg_split('#(<[^>]+>)#', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $code = 0;
        foreach ($seg as $j => $s) {
            if ($j % 2) { if (preg_match('#^<code\b#', $s)) $code++; elseif ($s === '</code>') $code--; continue; }
            if ($code > 0 || $s === '') continue;
            $seg[$j] = (string)preg_replace($re, $to, $s);
        }
        return implode('', $seg);
    }

    /** 글자 출력 + 줄머리 금칙 */
    public static function t(string $s): string { return self::keep(self::e($s)); }

    /** origin 형식: https://도메인[:포트], 끝 / 없음 */
    public static function validOrigin(string $o): bool { return (bool)preg_match('#^https://[A-Za-z0-9.-]+(:\d+)?$#', $o); }

    /** 마크다운 한 덩어리 → 한 줄 평문 (설명 메타용) */
    public static function plain(string $md, int $max): string
    {
        $t = preg_replace('/[`*_#>\[\]]|\((https?:[^)]*)\)/u', '', $md);
        $t = trim(preg_replace('/\s+/u', ' ', (string)$t));
        return mb_strlen($t) > $max ? rtrim(mb_substr($t, 0, $max - 1)) . '…' : $t;
    }

    /**
     * 최근 개정: 이슈마다 가장 최근 개정 한 건(없으면 마지막 확인일), 날짜 최신순·같은 날은 id 순.
     * @return list<array{issue:Issue,date:string,note:string}>
     */
    public function recentRevisions(int $n = 5): array
    {
        $rows = [];
        foreach ($this->repo->all() as $i) {
            $r = $i->revisions[0] ?? ['date' => $i->checked->format('Y-m-d'), 'note' => '내용 확인'];
            $rows[] = ['issue' => $i, 'date' => $r['date'], 'note' => $r['note']];
        }
        usort($rows, fn($a, $b) => [$b['date'], $a['issue']->id] <=> [$a['date'], $b['issue']->id]);
        return array_slice($rows, 0, $n);
    }

    /** 출력용 이스케이프 */
    public static function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

    /** @return array{status:int,type:string,body:string} */
    public function route(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') return $this->html(200, 'home', ['title' => '', 'desc' => self::HOME_DESC, 'groups' => $this->repo->groups(), 'byGroup' => $this->repo->byGroup()]);
        if ($this->pageOn($path)) {
            $meta = self::PAGES[$path];
            $vars = ['title' => $meta['title'], 'lead' => $meta['lead'], 'desc' => $meta['lead'], 'page' => $path,
                'crumbs' => [['홈', $this->url('')], [$meta['section'], null], [$meta['title'], null]]];
            if ($path === 'coverage') $vars['coverage'] = $this->coverage();
            if ($path === 'start') $vars['start'] = $this->repo->start();
            return $this->html(200, $path, $vars);
        }
        if (preg_match('#^issues/([^/]+)$#', $path, $m) && preg_match(self::ID_RE, $m[1]) && ($i = $this->repo->find($m[1]))) {
            return $this->html(200, 'issue', ['title' => $i->title, 'issue' => $i, 'current' => $i->id, 'group' => $i->group, 'wide' => true,
                'desc' => self::plain($i->sections['요약'] ?? $i->title, 160), 'ogType' => 'article',
                'crumbs' => [['홈', $this->url('')], ['보안 이슈', null], [$this->groupName($i->group), $this->url('') . '#grp-' . $i->group], [$i->id, null]]]);
        }
        if ($path === 'api/issues.json') {
            return $this->json(array_map(fn(Issue $i) => [
                'id' => $i->id, 'title' => $i->title, 'slug' => $i->slug, 'group' => $i->group, 'severity' => $i->severity,
                'site_only' => $i->siteOnly, 'depth' => $i->depth, 'languages' => $i->languages, 'maps' => $i->maps,
                'published' => $i->published->format('Y-m-d'), 'checked' => $i->checked->format('Y-m-d'),
                'url' => $this->url('issues/' . $i->id),
            ], $this->repo->all()));
        }
        if (preg_match('#^api/issues/([^/]+)\.json$#', $path, $m) && preg_match(self::ID_RE, $m[1]) && ($i = $this->repo->find($m[1]))) {
            return $this->json([
                'id' => $i->id, 'title' => $i->title, 'slug' => $i->slug, 'group' => $i->group, 'severity' => $i->severity,
                'site_only' => $i->siteOnly, 'depth' => $i->depth, 'languages' => $i->languages, 'maps' => $i->maps,
                ...$this->docMeta($i), 'sources' => $i->sources,
                'sections' => (object)$i->sections, 'code' => (object)$i->code,
                'url' => $this->url('issues/' . $i->id),
            ]);
        }
        if ($path === 'api/search.json') {
            return $this->json(array_map(fn(array $r) => [
                'id' => $r['id'], 'title' => $r['title'], 'group' => $r['group'], 'severity' => $r['severity'],
                'url' => $this->url('issues/' . $r['id']), 'text' => $r['text'],
            ], $this->repo->searchIndex()));
        }
        if ($path === 'api/coverage.json') return $this->json(['frameworks' => $this->coverage(), 'gaps' => Coverage::gaps($this->repo->coverage())]);
        return $this->html(404, 'error', ['title' => '찾을 수 없음', 'message' => '주소 변경 또는 삭제된 페이지']);
    }

    /** 표준별 대응표: 화면과 JSON 이 같이 쓴다. @return list<array<string,mixed>> */
    private function coverage(): array
    {
        $built = $this->repo->coverage();
        $out = [];
        foreach ($this->repo->frameworks() as $fw) {
            $items = [];
            foreach ($built[$fw['id']] ?? [] as $row) {
                $items[] = ['id' => $row['item'], 'title' => $row['title'], 'title_ko' => $row['title_ko'], 'issues' => $row['issues'], 'site_only' => $row['site_only'], 'verified' => $row['verified']];
            }
            $out[] = ['id' => $fw['id'], 'name' => $fw['name'], 'year' => $fw['year'], 'url' => $fw['url'], 'verified' => !in_array(false, array_column($items, 'verified'), true), 'items' => $items];
        }
        return $out;
    }

    /** @param array<string,mixed> $vars */
    public function render(string $template, array $vars): string
    {
        $site = $this;
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include __DIR__ . '/../templates/' . $template . '.php';
            return (string)ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /** @param array<string,mixed> $vars @return array{status:int,type:string,body:string} */
    private function html(int $status, string $template, array $vars): array
    {
        $content = $this->render($template, $vars);
        $errors = $this->mode === 'live' ? $this->repo->errors() : [];
        $body = $this->render('layout', [
            'title' => $vars['title'] ?? '', 'content' => $content, 'errors' => $errors,
            'current' => $vars['current'] ?? null, 'page' => $vars['page'] ?? null, 'wide' => $vars['wide'] ?? false,
            'group' => $vars['group'] ?? null, 'crumbs' => $vars['crumbs'] ?? [],
            'desc' => $vars['desc'] ?? self::DEFAULT_DESC, 'ogType' => $vars['ogType'] ?? 'website',
            'canonical' => $status === 200 && $this->setting('origin') !== '' ? $this->setting('origin') . $this->url($template === 'home' ? '' : ($vars['page'] ?? 'issues/' . ($vars['current'] ?? ''))) : null,
        ]);
        return ['status' => $status, 'type' => 'html', 'body' => $body];
    }

    /** @return array{status:int,type:string,body:string} */
    private function json(mixed $data): array
    {
        return ['status' => 200, 'type' => 'json', 'body' => json_encode($data, self::JSON_FLAGS) . "\n"];
    }
}
