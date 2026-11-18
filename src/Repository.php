<?php
/** issues/**.md 전체를 읽어 두는 저장소. 파일 하나가 틀려도 나머지는 로드한다. */
final class Repository
{
    /** @param list<Issue> $issues @param list<string> $errors @param array<int,array<string,mixed>> $groups */
    /** @param list<array<string,mixed>> $frameworks @param array<string,list<array<string,mixed>>> $coverage */
    private function __construct(private array $issues, private array $errors, private array $groups, private array $frameworks = [], private array $coverage = []) {}

    public static function load(string $root): Repository
    {
        $root = rtrim($root, '/');
        $errors = [];
        $groups = self::loadGroups($root . '/data/groups.json', $errors);
        $order = [];
        foreach ($groups as $g) $order[$g['id']] = $g['order'];

        $files = [];
        $dir = $root . '/issues';
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) if ($f->isFile() && strtolower($f->getExtension()) === 'md') $files[] = $f->getPathname();
        }
        sort($files);
        $issues = [];
        $seen = [];
        foreach ($files as $path) {
            try {
                $i = Issue::fromFile($path, $groups);
                if (isset($seen[$i->id])) throw new IssueError(basename($path) . ": id {$i->id} 가 {$seen[$i->id]} 와 겹칩니다");
                $seen[$i->id] = basename($path);
                $issues[] = $i;
            } catch (IssueError $e) {
                $errors[] = $e->getMessage();
            }
        }
        usort($issues, fn(Issue $a, Issue $b) => [$order[$a->group] ?? 999, $a->id] <=> [$order[$b->group] ?? 999, $b->id]);
        $repo = new Repository($issues, $errors, $groups);
        $repo->attachCoverage($root);
        return $repo;
    }

    /** 표준 항목 파일을 읽고 대응표를 만든다. 없는 항목은 오류 띠 목록에 넣고, 표는 있는 항목만으로 그린다. */
    private function attachCoverage(string $root): void
    {
        $this->frameworks = Coverage::loadFrameworks($root, $this->errors);
        try {
            $this->coverage = Coverage::build($this, $this->frameworks);
        } catch (CoverageError $e) {
            foreach ($e->problems as $p) $this->errors[] = $p;
            $this->coverage = Coverage::build($this, $this->frameworks, false);
        }
    }

    /**
     * 묶음 목록을 읽는다. 문제는 조용히 넘기지 않고 $errors 에 남긴다(서버 경로 없이 상대 이름만).
     * @param list<string> $errors @return list<array{id:string,name:string,order:int,site_only:bool,when:?string}>
     */
    private static function loadGroups(string $file, array &$errors): array
    {
        $name = 'data/groups.json';
        $raw = is_file($file) ? @file_get_contents($file) : false;
        if ($raw === false) { $errors[] = "$name: 파일을 읽을 수 없습니다"; return []; }
        $data = json_decode($raw, true);
        if (!is_array($data) || !array_is_list($data)) { $errors[] = "$name: 묶음 목록(JSON 배열) 형식이 아닙니다"; return []; }
        $out = [];
        $ids = [];
        foreach ($data as $n => $g) {
            $no = $n + 1;
            $ok = is_array($g)
                && isset($g['id'], $g['name'], $g['order'])
                && is_string($g['id']) && preg_match('/^[a-z0-9-]+$/', $g['id'])
                && is_string($g['name']) && trim($g['name']) !== ''
                && is_int($g['order']);
            if (!$ok) { $errors[] = "$name: {$no}번째 항목에 id·name·order 가 없거나 형식이 틀립니다"; continue; }
            if (isset($ids[$g['id']])) { $errors[] = "$name: id `{$g['id']}` 가 두 번 나옵니다"; continue; }
            $ids[$g['id']] = true;
            $when = isset($g['when']) && is_string($g['when']) && trim($g['when']) !== '' ? trim($g['when']) : null;
            $out[] = ['id' => $g['id'], 'name' => $g['name'], 'order' => $g['order'], 'site_only' => (bool)($g['site_only'] ?? false), 'when' => $when];
        }
        return $out;
    }

    /** @return list<Issue> */
    public function all(): array { return $this->issues; }

    /** @return list<array<string,mixed>> */
    public function frameworks(): array { return $this->frameworks; }

    /** @return array<string,list<array<string,mixed>>> */
    public function coverage(): array { return $this->coverage; }

    /** @return list<string> */
    public function errors(): array { return $this->errors; }

    /** @return array<int,array<string,mixed>> */
    public function groups(): array { return $this->groups; }

    /** @return array<string,list<Issue>> */
    public function byGroup(): array
    {
        $out = [];
        foreach ($this->issues as $i) $out[$i->group][] = $i;
        return $out;
    }

    public function find(string $id): ?Issue
    {
        foreach ($this->issues as $i) if ($i->id === $id) return $i;
        return null;
    }

    /** @return list<array{id:string,title:string,group:string,severity:string,text:string}> */
    public function searchIndex(): array
    {
        $out = [];
        foreach ($this->issues as $i) {
            $text = trim(($i->sections['요약'] ?? '') . ' ' . ($i->sections['점검 항목'] ?? ''));
            $text = trim(preg_replace('/\s+/u', ' ', preg_replace('/^\s*[-*]\s+/m', '', $text)));
            if (mb_strlen($text) > 300) $text = mb_substr($text, 0, 299) . '…';
            $out[] = ['id' => $i->id, 'title' => $i->title, 'group' => $i->group, 'severity' => $i->severity, 'text' => $text];
        }
        return $out;
    }
}
