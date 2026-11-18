<!-- generated from white-security issues; do not edit -->
# web · 웹 애플리케이션

When: 입력을 받아 DB·화면·파일·명령으로 넘기는 웹 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.

- `execute(` `executemany(` `cursor.` `query(` `raw(` `text(` `->query(` `->exec(` `->prepare(` `mysqli_query(` `pg_query(` `pool.query(` `sequelize.query(` `knex.raw(` `whereRaw(` → WS-INJ-001 SQL 인젝션
