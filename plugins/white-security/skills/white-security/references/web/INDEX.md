<!-- generated from white-security issues; do not edit -->
# web · 웹 애플리케이션

When: 입력을 받아 DB·화면·파일·명령으로 넘기는 웹 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Python · JavaScript · PHP · Laravel · Nginx

- `cursor.execute(` `->query(` `mysqli_query(` `pool.query(` `db.query(` `sequelize.query(` `knex.raw(` `whereRaw(` → WS-INJ-001 SQL 인젝션 (SQLi)
- `move_uploaded_file(` `getClientOriginalName(` `public_path(` `file_put_contents(` `storeAs(` `location ~ \.php$` `.htaccess` `AddHandler` → WS-SHELL-001 Web Shell
