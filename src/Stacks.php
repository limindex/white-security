<?php
/**
 * 코드 예시 키(스택) 목록. data/stacks.json 이 유일한 원본.
 * 이슈의 `languages` 와 `### <key>` 제목은 이 목록의 key 만 쓸 수 있다.
 * 항목: key(파일 이름·머리말에 쓰는 소문자) · label(화면 탭 이름) · lang(코드 하이라이트·문법 검사용 언어) · stack(주력 스택 묶음).
 * 선택: item(지원 스택 목록에 쓰는 이름, 예: 순수 PHP) · via(그 스택 예시가 없을 때 대신 덮는 키 — via 예시가 있으면 "이름(via 예시)"로 표시).
 */
final class Stacks
{
    private static ?array $cache = null;

    /** 읽는 파일의 문제는 $errors 에 남긴다(오류가 있는 항목은 뺀다). @param list<string> $errors @return array{groups:list<array{id:string,name:string}>,stacks:list<array{key:string,label:string,lang:string,stack:string}>} */
    public static function loadFile(string $file, array &$errors): array
    {
        $name = 'data/stacks.json';
        $empty = ['groups' => [], 'stacks' => []];
        $raw = is_file($file) ? @file_get_contents($file) : false;
        if ($raw === false) { $errors[] = "$name: 파일을 읽을 수 없습니다"; return $empty; }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['groups'], $data['stacks']) || !is_array($data['groups']) || !is_array($data['stacks'])) { $errors[] = "$name: groups·stacks 목록이 있는 JSON 객체여야 합니다"; return $empty; }
        $groups = [];
        foreach ($data['groups'] as $n => $g) {
            if (!is_array($g) || !isset($g['id'], $g['name']) || !is_string($g['id']) || !preg_match('/^[a-z0-9-]+$/', $g['id']) || !is_string($g['name']) || trim($g['name']) === '') { $errors[] = "$name: groups[" . ($n + 1) . '] 에 id·name 이 없거나 형식이 틀립니다'; continue; }
            if (isset($groups[$g['id']])) { $errors[] = "$name: groups id `{$g['id']}` 가 두 번 나옵니다"; continue; }
            $groups[$g['id']] = ['id' => $g['id'], 'name' => trim($g['name'])];
        }
        $stacks = [];
        foreach ($data['stacks'] as $n => $s) {
            $no = $n + 1;
            $ok = is_array($s) && isset($s['key'], $s['label'], $s['lang'], $s['stack'])
                && is_string($s['key']) && preg_match('/^[a-z0-9][a-z0-9-]*$/', $s['key'])
                && is_string($s['label']) && trim($s['label']) !== ''
                && is_string($s['lang']) && preg_match('/^[a-z0-9+#-]+$/', $s['lang'])
                && is_string($s['stack']);
            if (!$ok) { $errors[] = "$name: stacks[$no] 에 key·label·lang·stack 이 없거나 형식이 틀립니다(key 는 소문자·숫자·하이픈)"; continue; }
            if (isset($stacks[$s['key']])) { $errors[] = "$name: key `{$s['key']}` 가 두 번 나옵니다"; continue; }
            if (!isset($groups[$s['stack']])) { $errors[] = "$name: key `{$s['key']}` 의 stack `{$s['stack']}` 이(가) groups 에 없습니다"; continue; }
            if (isset($s['item']) && (!is_string($s['item']) || trim($s['item']) === '')) { $errors[] = "$name: key `{$s['key']}` 의 item 은 빈칸이 아닌 문자열이어야 합니다"; continue; }
            if (isset($s['via']) && (!is_string($s['via']) || !preg_match('/^[a-z0-9][a-z0-9-]*$/', $s['via']) || $s['via'] === $s['key'])) { $errors[] = "$name: key `{$s['key']}` 의 via 는 다른 스택 key 여야 합니다"; continue; }
            $stacks[$s['key']] = ['key' => $s['key'], 'label' => trim($s['label']), 'lang' => $s['lang'], 'stack' => $s['stack']]
                + (isset($s['item']) ? ['item' => trim($s['item'])] : []) + (isset($s['via']) ? ['via' => $s['via']] : []);
        }
        foreach ($stacks as $k => $st) if (isset($st['via']) && !isset($stacks[$st['via']])) { $errors[] = "$name: key `$k` 의 via `{$st['via']}` 이(가) 목록에 없습니다"; unset($stacks[$k]['via']); }
        return ['groups' => array_values($groups), 'stacks' => array_values($stacks)];
    }

    /** 프로젝트의 data/stacks.json (한 번만 읽음). 문제가 있으면 예외 — 허용 목록이 없으면 이슈를 검증할 수 없다. */
    private static function data(): array
    {
        if (self::$cache === null) {
            $errs = [];
            self::$cache = self::loadFile(__DIR__ . '/../data/stacks.json', $errs);
            if ($errs) throw new RuntimeException(implode(' / ', $errs));
        }
        return self::$cache;
    }

    /** @return list<string> 허용 키 */
    public static function keys(): array { return array_column(self::data()['stacks'], 'key'); }

    /** @return array<string,array{key:string,label:string,lang:string,stack:string}> */
    public static function byKey(): array { return array_column(self::data()['stacks'], null, 'key'); }

    /** @return list<array{id:string,name:string}> */
    public static function groups(): array { return self::data()['groups']; }

    /** 화면 탭 이름. 목록에 없는 키는 키 그대로. */
    public static function label(string $key): string { return self::byKey()[$key]['label'] ?? $key; }

    /** 코드 하이라이트·문법 검사용 언어. 목록에 없는 키는 키 그대로. */
    public static function lang(string $key): string { return self::byKey()[$key]['lang'] ?? $key; }
}
