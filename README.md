# PHPTomb

PHPTomb is a small **MySQL query builder** for plain (“core”) PHP projects. It gives you a fluent API similar in spirit to Laravel’s query builder (`DB::table('users')->where(...)->get()`) without adopting a full framework.

**Requirements**

- PHP **8.0+**
- Extension **`mysqli`**
- **MySQL** (or MariaDB with compatible SQL)

---

## Integrating into a core PHP project

### 1. Add the library to your project

**Option A — Composer (recommended)**

If this package is published on Packagist, from your project root:

```bash
composer require phptomb/phptomb
```

Otherwise add a [path](https://getcomposer.org/doc/05-repositories.md#path) or [VCS](https://getcomposer.org/doc/05-repositories.md#loading-a-package-from-a-vcs-repository) repository in your `composer.json`, then run `composer require` as usual.

Composer autoloads the `DB` class from the `tomb/` folder. In your PHP files:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

**Option B — Copy files manually**

Copy the `tomb/` directory into your project (for example `lib/tomb/` or `includes/tomb/`). You also need a **`server.php`** next to `tomb/` **or** you configure the database entirely via **environment variables** (see below).

Load the class with a path that matches your layout:

```php
require_once __DIR__ . '/tomb/DB.php';
```

PHPTomb resolves `server.php` from **`dirname(tomb/DB.php)/../server.php`** — i.e. one level **above** the `tomb/` folder. If you put `tomb` under `includes/tomb/`, place `server.php` under `includes/server.php`.

### 2. Configure the database connection

PHPTomb reads settings in this order:

1. **Environment variables** (if set): `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`
2. Otherwise **`server.php`** (after it is loaded): `$SERVER`, `$DB_PORT`, `$USER`, `$PASS`, `$DBNAME`

Example `server.php` at the same level as the `tomb/` folder:

```php
<?php
$SERVER = 'localhost';
$DB_PORT = 3306;
$USER = 'myuser';
$PASS = 'secret';
$DBNAME = 'myapp';
```

For production, prefer **environment variables** (web server, `.env` loader, Docker, systemd) so credentials are not committed. See `env.example` in this repository.

### 3. Bootstrap once per request

In a typical core PHP app you include the bootstrap at the top of each script that uses the database, or once from a shared `init.php` / `config.php`:

```php
<?php
declare(strict_types=1);

// Optional: enable SQL logging to project_root/logs/ (off by default)
// define('TOMB_LOG_ENABLED', true);

require_once __DIR__ . '/vendor/autoload.php';   // Composer
// require_once __DIR__ . '/tomb/DB.php';        // manual install
```

The `DB` class lives in the **global namespace** (there is no `namespace` declaration in `tomb/DB.php`).

There is **no** `session_start()` inside PHPTomb; start the session in your own bootstrap if you need it.

### 4. Use the query builder

`get()` returns an **array** of rows (`stdClass` objects). `first()` returns **`stdClass|null`**.

```php
$users = DB::table('users')->orderBy('id', 'DESC')->get();

$user = DB::table('users')->where('email', 'ada@example.com')->first();

$total = DB::table('users')->where('active', 1)->count();
```

**Insert** returns success as a boolean; use **`DB::getLastInsertId()`** after an insert when you need the new primary key:

```php
$ok = DB::table('users')->insert([
    'name'  => 'Ada',
    'email' => 'ada@example.com',
]);
$id = DB::getLastInsertId();
```

**Update** requires a **`where(...)`** clause (to avoid accidental full-table updates):

```php
DB::table('users')->where('id', $id)->update(['name' => 'Augusta']);
```

**Delete** also requires a non-empty **`where`** (plain `delete()` without conditions returns `false`).

**Raw SQL** runs on the shared connection:

```php
$result = DB::raw('SELECT COUNT(*) AS c FROM users');
```

**Transactions** (InnoDB):

```php
DB::beginTransaction();
try {
    DB::table('users')->insert([/* ... */]);
    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    throw $e;
}
```

**Connection handle** (advanced use):

```php
$mysqli = DB::connection();
```

One **shared** `mysqli` instance is used per request. For long-running workers or tests you can close it with `DB::resetConnection()`.

---

## API overview

| Method | Purpose |
|--------|---------|
| `DB::table('name')` | Start a query on a table |
| `DB::create('name')` | Table builder (with `addColumn`, `execute`) |
| `select('col1, col2')` | Select clause (comma-separated SQL fragment) |
| `distinct()` | `SELECT DISTINCT` |
| `where($k, $v)` / `where($k, $op, $v)` / `where([...])` | `AND` conditions |
| `orWhere(...)` | `OR` condition |
| `whereIn` / `whereNotIn` | `IN` / `NOT IN` |
| `whereNull` / `whereNotNull` | `IS NULL` / `IS NOT NULL` |
| `leftJoin` / `innerJoin` / `rightJoin` | Joins (`$table`, `$leftExpr`, `$rightExpr`) |
| `groupBy` / `having` / `orderBy` | Grouping, `HAVING`, `ORDER BY` (`orderBy` supports `table.column`) |
| `limit($offset, $rowCount)` | MySQL `LIMIT offset, rowCount` |
| `get()` | All matching rows (**array**) |
| `first()` | First row or **null** |
| `exists()` | Whether any row matches |
| `count()` | Row count (`COUNT(*)`, respects `GROUP BY`) |
| `insert` / `update` | Insert / update rows |
| `delete()` | Delete with **required** `where` |
| `truncateTable()` | `TRUNCATE TABLE` (use with care) |
| `DB::raw($sql)` | Run arbitrary SQL |
| `DB::getLastInsertId()` | Last insert id on the shared connection |
| `DB::beginTransaction` / `commit` / `rollback` | Transactions |
| `DB::resetConnection()` | Close shared connection |

**Schema helper** (optional, for quick prototypes):

```php
DB::create('products')
    ->addColumn('name', 'varchar', 255, 'NOT NULL')
    ->addColumn('price', 'int', 11, 'NOT NULL')
    ->execute();
```

Creates `id`, `created_at`, `updated_at` automatically.

---

## Security and data handling

- String and structured values in `where`, `insert`, and `update` are passed through **`mysqli::real_escape_string`** (and related rules for `NULL`, booleans, numbers). Prefer **parameterized queries** for highly sensitive workloads; PHPTomb is aimed at pragmatic core PHP apps with a balance of ergonomics and safety.
- **Identifiers** (table/column names in the builder) are restricted to `[a-zA-Z0-9_.]` and split for `table.column`; do not pass user-controlled strings as table or column names.
- **`having()`** and **`DB::raw()`** embed SQL fragments as supplied. Use only **trusted, application-defined** strings there—never concatenate end-user input.
- **Logging** of full SQL is **disabled by default**. To enable: `define('TOMB_LOG_ENABLED', true);` **before** including `DB.php`. Logs are written under **`logs/`** at the project root (next to `server.php` / `tomb/`).

### Security automation

- **Dependency audit** (known CVEs in Composer packages): `composer security:audit`
- **SQL-injection regression tests** (MySQL, same Docker stack as E2E): `composer test:security`  
  Full integration + security suite: `composer test:e2e` or `./docker/e2e.sh`

Tests live under `tests/Security/` and assert that typical builder paths treat payloads as data, while documenting risky APIs (`having`, `raw`).

---

## Development and Docker

- **Unit tests** (no database): `composer test`
- **End-to-end + security tests** (PHP + MySQL in Docker): `./docker/e2e.sh` or `composer test:e2e`  
  Includes chained `where` / `orWhere`, `groupBy` + `having`, multi-column `orderBy`, and `limit` combinations (`tests/E2E/ComplexQueryTest.php`).

See `docker-compose.yml`, `Dockerfile`, and `docker/mysql/init/` for a ready-made stack and schema used by E2E tests.

---

## License

MIT (see `composer.json`).
