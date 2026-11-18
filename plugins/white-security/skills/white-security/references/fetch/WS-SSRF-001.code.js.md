<!-- generated from white-security issues; do not edit -->
# WS-SSRF-001 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
app.get("/preview", async (req, res) => {
  const r = await fetch(req.query.url); // 내부 주소도 대신 열어 준다
  res.send(await r.text());
});
```

✓ 안전 코드 (safe example)

```js
import dns from "node:dns";
import https from "node:https";
import net from "node:net";

const blocked = new net.BlockList();
for (const [ip, bits] of [["0.0.0.0", 8], ["10.0.0.0", 8], ["100.64.0.0", 10], ["127.0.0.0", 8], ["169.254.0.0", 16],
  ["172.16.0.0", 12], ["192.0.0.0", 24], ["192.168.0.0", 16], ["198.18.0.0", 15], ["224.0.0.0", 3]]) blocked.addSubnet(ip, bits, "ipv4");

function checkedLookup(host, opts, cb) { // 실제로 접속할 IP 를 여기서 검사한다
  dns.lookup(host, { family: 4 }, (err, ip) => {
    if (err) return cb(err);
    if (blocked.check(ip, "ipv4")) return cb(new Error("내부 주소로는 요청하지 않습니다"));
    opts.all ? cb(null, [{ address: ip, family: 4 }]) : cb(null, ip, 4);
  });
}

export function safeFetch(url, limit = 2_000_000) {
  const u = new URL(url);
  if (u.protocol !== "https:" || u.port || u.username) throw new Error("https 기본 포트 주소만 받습니다");
  if (net.isIP(u.hostname.replace(/^\[|\]$/g, ""))) throw new Error("IP 주소는 받지 않습니다"); // IP 는 lookup 을 거치지 않는다
  return new Promise((resolve, reject) => {
    const req = https.get(u, { lookup: checkedLookup, timeout: 5000 }, (res) => { // 리다이렉트는 따라가지 않는다
      const chunks = []; let size = 0;
      res.on("data", (c) => { size += c.length; if (size > limit) req.destroy(new Error("너무 큽니다")); else chunks.push(c); });
      res.on("end", () => resolve(Buffer.concat(chunks)));
    });
    req.on("timeout", () => req.destroy(new Error("시간 초과")));
    req.on("error", reject);
  });
}
```
