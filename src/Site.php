<?php
/** 경로 하나를 받아 {status,type,body} 를 돌려주는 라우터. live(서버)와 static(내보내기) 공용. */
final class Site
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    private const PAGES = ['coverage' => '적용 범위', 'install' => '설치', 'changelog' => '변경 내역', 'about' => '소개'];
    private const ID_RE = '/^WS-[A-Z]{2,5}-\d{3}$/';

    public function __construct(public readonly Repository $repo, private string $baseUrl, public readonly string $mode)
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function url(string $path): string { return $this->baseUrl . ltrim($path, '/'); }

    /** 출력용 이스케이프 */
    public static function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

    /** @return array{status:int,type:string,body:string} */
    public function route(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') return $this->html(200, 'home', ['title' => '화이트 보안', 'groups' => $this->repo->groups(), 'byGroup' => $this->repo->byGroup()]);
        if (isset(self::PAGES[$path])) {
            $vars = ['title' => self::PAGES[$path]];
            if ($path === 'coverage') $vars['coverage'] = $this->coverage();
            return $this->html(200, $path, $vars);
        }
        if (preg_match('#^issues/([^/]+)$#', $path, $m) && preg_match(self::ID_RE, $m[1]) && ($i = $this->repo->find($m[1]))) {
            return $this->html(200, 'issue', ['title' => $i->title, 'issue' => $i]);
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
        if ($path === 'api/coverage.json') return $this->json((object)$this->coverage());
        return $this->html(404, 'error', ['title' => '찾을 수 없음', 'message' => '찾는 페이지가 없습니다.']);
    }

    /** 표준 항목 → 이슈 id 목록. (상세 계산은 이후 Coverage 가 대체) @return array<string,array<string,list<string>>> */
    private function coverage(): array
    {
        $out = [];
        foreach ($this->repo->all() as $i) {
            foreach ($i->maps as $key => $codes) foreach ($codes as $c) $out[$key][$c][] = $i->id;
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
        $body = $this->render('layout', ['title' => $vars['title'] ?? '', 'content' => $content, 'errors' => $errors]);
        return ['status' => $status, 'type' => 'html', 'body' => $body];
    }

    /** @return array{status:int,type:string,body:string} */
    private function json(mixed $data): array
    {
        return ['status' => 200, 'type' => 'json', 'body' => json_encode($data, self::JSON_FLAGS) . "\n"];
    }
}
