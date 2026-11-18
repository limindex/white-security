<?php
/** 경로 하나를 받아 {status,type,body} 를 돌려주는 라우터. live(서버)와 static(내보내기) 공용. */
final class Site
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    private const PAGES = ['coverage' => '적용 범위', 'install' => '설치', 'changelog' => '변경 내역', 'about' => '소개'];
    private const ID_RE = '/^WS-[A-Z]{2,5}-\d{3}$/';
    /** 마지막 확인일이 이 일수를 넘으면 "확인 필요" */
    public const STALE_DAYS = 180;
    public const SEVERITY_LABELS = ['high' => '위험', 'medium' => '주의', 'low' => '참고'];
    public const LANG_LABELS = ['python' => 'Python', 'php' => 'PHP', 'js' => 'JavaScript', 'ts' => 'TypeScript', 'go' => 'Go', 'java' => 'Java', 'ruby' => 'Ruby', 'shell' => 'Shell', 'yaml' => 'YAML'];

    public readonly DateTimeImmutable $today;

    public function __construct(public readonly Repository $repo, private string $baseUrl, public readonly string $mode, ?DateTimeImmutable $today = null)
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

    /** 마지막 확인일이 STALE_DAYS 를 넘었나 (주입한 오늘 기준) */
    public function isStale(Issue $i): bool
    {
        return $i->checked < $this->today && $this->today->diff($i->checked)->days > self::STALE_DAYS;
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

    /** 심각도 배지 HTML (위험 빨강 · 주의 주황 · 참고 회색) */
    public static function severityBadge(string $severity, string $suffix = ''): string
    {
        $label = self::SEVERITY_LABELS[$severity] ?? $severity;
        return '<span class="ws-badge ws-badge--' . self::e($severity) . '">' . self::e($label . $suffix) . '</span>';
    }

    /** 마지막 확인일이 오래됐을 때만 "확인 필요" 배지 */
    public function staleBadge(Issue $i): string
    {
        if (!$this->isStale($i)) return '';
        return '<span class="ws-badge ws-badge--stale" title="마지막 확인 뒤 ' . self::STALE_DAYS . '일 넘게 지남">확인 필요</span>';
    }

    /** 확인일 최신순 n건 (같은 날이면 id 순) @return list<Issue> */
    public function recent(int $n = 5): array
    {
        $all = $this->repo->all();
        usort($all, fn(Issue $a, Issue $b) => [$b->checked, $a->id] <=> [$a->checked, $b->id]);
        return array_slice($all, 0, $n);
    }

    /** 출력용 이스케이프 */
    public static function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

    /** @return array{status:int,type:string,body:string} */
    public function route(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') return $this->html(200, 'home', ['title' => '', 'groups' => $this->repo->groups(), 'byGroup' => $this->repo->byGroup()]);
        if (isset(self::PAGES[$path])) {
            $vars = ['title' => self::PAGES[$path], 'page' => $path];
            if ($path === 'coverage') $vars['coverage'] = $this->coverage();
            return $this->html(200, $path, $vars);
        }
        if (preg_match('#^issues/([^/]+)$#', $path, $m) && preg_match(self::ID_RE, $m[1]) && ($i = $this->repo->find($m[1]))) {
            return $this->html(200, 'issue', ['title' => $i->title, 'issue' => $i, 'current' => $i->id, 'wide' => true]);
        }
        if ($path === 'api/issues.json') {
            return $this->json(array_map(fn(Issue $i) => [
                'id' => $i->id, 'title' => $i->title, 'slug' => $i->slug, 'group' => $i->group, 'severity' => $i->severity,
                'site_only' => $i->siteOnly, 'depth' => $i->depth, 'languages' => $i->languages, 'maps' => $i->maps,
                'url' => $this->url('issues/' . $i->id),
            ], $this->repo->all()));
        }
        if (preg_match('#^api/issues/([^/]+)\.json$#', $path, $m) && preg_match(self::ID_RE, $m[1]) && ($i = $this->repo->find($m[1]))) {
            return $this->json([
                'id' => $i->id, 'title' => $i->title, 'slug' => $i->slug, 'group' => $i->group, 'severity' => $i->severity,
                'site_only' => $i->siteOnly, 'depth' => $i->depth, 'languages' => $i->languages, 'maps' => $i->maps,
                'checked' => $i->checked->format('Y-m-d'), 'sources' => $i->sources,
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
        return $this->html(404, 'error', ['title' => '찾을 수 없음', 'message' => '주소가 바뀌었거나 없는 페이지입니다.']);
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
        ]);
        return ['status' => $status, 'type' => 'html', 'body' => $body];
    }

    /** @return array{status:int,type:string,body:string} */
    private function json(mixed $data): array
    {
        return ['status' => 200, 'type' => 'json', 'body' => json_encode($data, self::JSON_FLAGS) . "\n"];
    }
}
