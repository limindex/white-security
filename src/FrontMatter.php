<?php
class FrontMatterError extends RuntimeException {}

/**
 * 외부 YAML 확장 없이 쓰는 작은 머리말 파서.
 * 지원: 스칼라, 따옴표 문자열, 인라인 목록, 블록 목록, 중첩 맵, 맵 목록. 그 밖은 FrontMatterError.
 */
final class FrontMatter
{
    /** @return array{0: array, 1: string} */
    public static function split(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        if (str_starts_with($raw, "\xEF\xBB\xBF")) $raw = substr($raw, 3);
        $lines = explode("\n", $raw);
        if (rtrim($lines[0]) !== '---') throw new FrontMatterError('줄 1: 머리말이 `---` 로 시작해야 합니다');
        $end = null;
        for ($i = 1; $i < count($lines); $i++) {
            if (rtrim($lines[$i]) === '---') { $end = $i; break; }
        }
        if ($end === null) throw new FrontMatterError('머리말을 닫는 `---` 가 없습니다');

        $rows = [];
        for ($i = 1; $i < $end; $i++) {
            $line = rtrim($lines[$i]);
            $no = $i + 1;
            if (preg_match('/^[ ]*\t/', $line)) throw new FrontMatterError("줄 $no: 들여쓰기에 탭을 쓸 수 없습니다 (공백만)");
            if (trim($line) === '' || preg_match('/^\s*#/', $line)) continue;
            preg_match('/^( *)/', $line, $m);
            $rows[] = ['no' => $no, 'indent' => strlen($m[1]), 'text' => substr($line, strlen($m[1]))];
        }
        $pos = 0;
        $data = [];
        if ($rows) {
            if ($rows[0]['indent'] !== 0) throw new FrontMatterError("줄 {$rows[0]['no']}: 첫 줄은 들여쓰기 없이 시작해야 합니다");
            $data = self::parseMap($rows, $pos, 0);
            if ($pos < count($rows)) throw new FrontMatterError("줄 {$rows[$pos]['no']}: 해석할 수 없는 줄입니다");
        }
        $body = implode("\n", array_slice($lines, $end + 1));
        return [$data, ltrim($body, "\n")];
    }

    private static function parseBlock(array &$rows, int &$pos, int $indent): array
    {
        return str_starts_with($rows[$pos]['text'], '- ') || $rows[$pos]['text'] === '-'
            ? self::parseList($rows, $pos, $indent)
            : self::parseMap($rows, $pos, $indent);
    }

    private static function parseMap(array &$rows, int &$pos, int $indent): array
    {
        $out = [];
        while ($pos < count($rows) && $rows[$pos]['indent'] === $indent) {
            $r = $rows[$pos];
            if (!preg_match('/^([A-Za-z0-9_][A-Za-z0-9_-]*):(?:[ ]+(.*))?$/', $r['text'], $m)) {
                throw new FrontMatterError("줄 {$r['no']}: `키: 값` 형식이 아닙니다");
            }
            $key = $m[1];
            if (array_key_exists($key, $out)) throw new FrontMatterError("줄 {$r['no']}: 키 `$key` 가 중복됩니다");
            $val = isset($m[2]) ? trim($m[2]) : '';
            $pos++;
            if ($val !== '' && !str_starts_with($val, '#')) {
                $out[$key] = self::parseInline($val, $r['no']);
                continue;
            }
            // 값이 비어 있으면 아래 줄의 중첩 블록
            if ($pos < count($rows)
                && ($rows[$pos]['indent'] > $indent
                    || ($rows[$pos]['indent'] === $indent && str_starts_with($rows[$pos]['text'], '- ')))) {
                $out[$key] = self::parseBlock($rows, $pos, $rows[$pos]['indent']);
            } else {
                $out[$key] = null;
            }
        }
        if ($pos < count($rows) && $rows[$pos]['indent'] > $indent) {
            throw new FrontMatterError("줄 {$rows[$pos]['no']}: 들여쓰기가 맞지 않습니다");
        }
        return $out;
    }

    private static function parseList(array &$rows, int &$pos, int $indent): array
    {
        $out = [];
        while ($pos < count($rows) && $rows[$pos]['indent'] === $indent
            && (str_starts_with($rows[$pos]['text'], '- ') || $rows[$pos]['text'] === '-')) {
            $r = $rows[$pos];
            $rest = ltrim(substr($r['text'], 1));
            if ($rest === '') throw new FrontMatterError("줄 {$r['no']}: 빈 목록 항목은 지원하지 않습니다");
            if (preg_match('/^[A-Za-z0-9_][A-Za-z0-9_-]*:(?:[ ]|$)/', $rest)) {
                // 맵 항목: 첫 키를 항목 내용의 들여쓰기 위치로 옮겨 맵으로 읽는다
                $inner = $indent + (strlen($r['text']) - strlen($rest));
                $rows[$pos] = ['no' => $r['no'], 'indent' => $inner, 'text' => $rest];
                $out[] = self::parseMap($rows, $pos, $inner);
            } else {
                $out[] = self::parseInline($rest, $r['no']);
                $pos++;
            }
        }
        if ($pos < count($rows) && $rows[$pos]['indent'] > $indent) {
            throw new FrontMatterError("줄 {$rows[$pos]['no']}: 들여쓰기가 맞지 않습니다");
        }
        return $out;
    }

    /** 한 줄 값: 따옴표 문자열 · 인라인 목록 · 스칼라 */
    private static function parseInline(string $s, int $no): mixed
    {
        $s = trim(self::stripComment($s));
        if ($s === '') throw new FrontMatterError("줄 $no: 값이 비어 있습니다");
        if ($s[0] === '[') {
            if (substr($s, -1) !== ']') throw new FrontMatterError("줄 $no: 인라인 목록의 `]` 가 없습니다");
            $inner = trim(substr($s, 1, -1));
            if ($inner === '') return [];
            $items = [];
            foreach (self::splitCommas($inner, $no) as $part) {
                $part = trim($part);
                if ($part === '') throw new FrontMatterError("줄 $no: 목록에 빈 항목이 있습니다");
                if ($part[0] === '[' || $part[0] === '{') throw new FrontMatterError("줄 $no: 중첩 목록은 지원하지 않습니다");
                $items[] = self::scalar($part, $no);
            }
            return $items;
        }
        if ($s[0] === '{') throw new FrontMatterError("줄 $no: `{}` 맵 표기는 지원하지 않습니다");
        if (in_array($s[0], ['&', '*', '!', '|', '>', '%', '@', '`'], true)) {
            throw new FrontMatterError("줄 $no: 지원하지 않는 문법입니다 ({$s[0]})");
        }
        return self::scalar($s, $no);
    }

    private static function scalar(string $s, int $no): mixed
    {
        $q = $s[0];
        if ($q === '"' || $q === "'") {
            if (strlen($s) < 2 || substr($s, -1) !== $q) throw new FrontMatterError("줄 $no: 따옴표가 닫히지 않았습니다");
            $in = substr($s, 1, -1);
            if ($q === '"') return preg_replace_callback('/\\\\(.)/su', fn($m) => match ($m[1]) { 'n' => "\n", 't' => "\t", default => $m[1] }, $in);
            return str_replace("''", "'", $in);
        }
        if ($s === 'true') return true;
        if ($s === 'false') return false;
        if ($s === 'null' || $s === '~') return null;
        if (preg_match('/^-?(0|[1-9][0-9]*)$/', $s)) return (int)$s;
        return $s;
    }

    /** 따옴표 밖의 ` #` 부터 주석 */
    private static function stripComment(string $s): string
    {
        $quote = null;
        $n = strlen($s);
        for ($i = 0; $i < $n; $i++) {
            $c = $s[$i];
            if ($quote !== null) {
                if ($c === '\\' && $quote === '"') { $i++; continue; }
                if ($c === $quote) $quote = null;
            } elseif (($c === '"' || $c === "'") && ($i === 0 || in_array($s[$i - 1], [' ', '[', ','], true))) {
                $quote = $c;
            } elseif ($c === '#' && $i > 0 && $s[$i - 1] === ' ') {
                return substr($s, 0, $i);
            }
        }
        return $s;
    }

    private static function splitCommas(string $s, int $no): array
    {
        $parts = []; $cur = ''; $quote = null; $n = strlen($s);
        for ($i = 0; $i < $n; $i++) {
            $c = $s[$i];
            if ($quote !== null) {
                $cur .= $c;
                if ($c === '\\' && $quote === '"' && $i + 1 < $n) { $cur .= $s[++$i]; continue; }
                if ($c === $quote) $quote = null;
            } elseif ($c === '"' || $c === "'") { $quote = $c; $cur .= $c; }
            elseif ($c === ',') { $parts[] = $cur; $cur = ''; }
            else $cur .= $c;
        }
        if ($quote !== null) throw new FrontMatterError("줄 $no: 따옴표가 닫히지 않았습니다");
        $parts[] = $cur;
        return $parts;
    }
}
