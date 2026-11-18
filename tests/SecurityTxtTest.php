<?php
// /.well-known/security.txt (RFC 9116): 제보 창구 = GitHub 비공개 취약점 제보, 만료일·언어, origin 이 있을 때만 Canonical
require_once __DIR__ . '/support.php';

const ST_JSON = '{"github": "https://github.com/limindex/white-security", "security_expires": "2027-11-19T00:00:00Z"}';
const ST_CONTACT = 'Contact: https://github.com/limindex/white-security/security/advisories/new';

function st_site(?string $json, ?string $origin = null): Site
{
    $root = make_root();
    if ($json !== null) file_put_contents($root . '/data/site.json', $json);
    return new Site(Repository::load($root), '/white-security/', 'static', new DateTimeImmutable('2026-11-19'), $origin);
}

/** 임시 루트(site.json 포함)에 build.php 실행. @return array{0:int,1:string,2:string} */
function st_build(string $json, string $today = '2026-11-19', string $args = ''): array
{
    $root = make_root();
    file_put_contents($root . '/data/site.json', $json);
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../build.php') . ' --root=' . escapeshellarg($root)
        . ' --out=dist --today=' . $today . ' ' . $args . ' 2>&1';
    exec($cmd, $lines, $code);
    return [$code, implode("\n", $lines), $root];
}

test('security.txt: Contact·Expires·Preferred-Languages·Policy, origin 없으면 Canonical 없음', function () {
    $r = st_site(ST_JSON)->route('.well-known/security.txt');
    assert_same(200, $r['status']);
    assert_same('text', $r['type']);
    assert_same(ST_CONTACT . "\nExpires: 2027-11-19T00:00:00Z\nPreferred-Languages: ko, en\n"
        . "Policy: https://github.com/limindex/white-security/security/policy\n", $r['body']);
});

test('security.txt: origin 이 있으면 Canonical 은 그 주소의 security.txt 하나', function () {
    $body = st_site(ST_JSON, 'https://example.org')->route('.well-known/security.txt')['body'];
    assert_contains("\nCanonical: https://example.org/white-security/.well-known/security.txt\n", $body);
    assert_same(1, substr_count($body, 'Canonical:'));
    $body = st_site(substr(ST_JSON, 0, -1) . ', "origin": "https://example.net"}')->route('/.well-known/security.txt/')['body'];
    assert_contains('Canonical: https://example.net/white-security/.well-known/security.txt', $body);
});

test('security.txt: github·security_expires 가 없으면 만들지 않음(404), 틀린 만료 형식은 설정 오류', function () {
    assert_same(404, st_site(null)->route('.well-known/security.txt')['status']);
    assert_same(404, st_site('{"github": "https://github.com/limindex/white-security"}')->route('.well-known/security.txt')['status']);
    assert_same(404, st_site('{"security_expires": "2027-11-19T00:00:00Z"}')->route('.well-known/security.txt')['status']);
    foreach (['2027-11-19', '2027-11-19T00:00:00+09:00', '2027-13-01T00:00:00Z', "2027-11-19T00:00:00Z\nContact: x"] as $bad) {
        $site = st_site(json_encode(['github' => 'https://github.com/limindex/white-security', 'security_expires' => $bad]));
        assert_true(count($site->repo->errors()) === 1 && str_contains($site->repo->errors()[0], 'security_expires'), "형식 오류: $bad");
        assert_same(404, $site->route('.well-known/security.txt')['status'], "만들지 않음: $bad");
    }
});

test('build: dist/.well-known/security.txt 를 만들고, sitemap 에는 넣지 않음', function () {
    [$code, $out, $root] = st_build(ST_JSON, '2026-11-19', '--origin=https://example.org');
    assert_same(0, $code, $out);
    $txt = (string)@file_get_contents("$root/dist/.well-known/security.txt");
    assert_contains(ST_CONTACT, $txt);
    assert_contains('Canonical: https://example.org/white-security/.well-known/security.txt', $txt);
    assert_true(!str_contains((string)file_get_contents("$root/dist/sitemap.xml"), 'security.txt'), 'sitemap 에 없음');
    assert_true(is_file("$root/dist/.nojekyll"), 'Pages 가 . 으로 시작하는 폴더를 그대로 내보냄');
});

test('build: security_expires 가 지났으면 빌드 실패, 설정이 없으면 만들지 않고 알림', function () {
    [$code, $out] = st_build(ST_JSON, '2027-11-19');
    assert_same(1, $code, $out);
    assert_contains('security_expires', $out);
    [$code, $out, $root] = st_build('{"github": "https://github.com/limindex/white-security"}');
    assert_same(0, $code, $out);
    assert_true(!file_exists("$root/dist/.well-known"), 'security.txt 없음');
    assert_contains('security.txt 는 만들지 않았습니다', $out);
});

test('data/site.json: security_expires 가 있고 올바른 형식, 제보 창구는 이 저장소', function () {
    $site = new Site(Repository::load(__DIR__ . '/..'), '/white-security/', 'static');
    assert_true(Site::securityExpires($site->setting('security_expires')) !== null, 'security_expires');
    $txt = (string)$site->securityTxt();
    assert_contains('Contact: https://github.com/' . $site->repoSlug() . '/security/advisories/new', $txt);
    assert_true(!preg_match('/mailto:|@/', $txt), '개인 이메일 없음');
});
