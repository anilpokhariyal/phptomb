<?php
declare(strict_types=1);

/**
 * PHPTomb — lightweight MySQL query builder.
 *
 * Optional bootstrap (set before including this file):
 * - TOMB_LOG_ENABLED (bool): enable SQL logging to logs/
 */

//credit erevolutions (india)
// For OPEN SOURCE

class DB
{
    private static ?mysqli $sharedConnection = null;

    private string $__table = '';
    private mysqli $connect;
    private string $joinQry = '';
    private string $where = '';
    private string $having = '';
    private string $limitClause = '';
    private bool $distinct = false;

    /** @var list<string> */
    private array $groupByParts = [];

    /** @var list<string> */
    private array $orderByParts = [];

    private string $andDeli = ' AND ';
    private string $orDeli = ' OR ';
    private string $commaDeli = ', ';
    private string $selects = ' * ';

    private string $addQry = '';

    public function __construct(string $table)
    {
        $this->__table = $this->escapeIdentifier($table);
        $this->connect = self::connection();
    }

    /**
     * Shared mysqli instance (one connection per request).
     */
    public static function connection(): mysqli
    {
        if (self::$sharedConnection !== null) {
            return self::$sharedConnection;
        }

        $config = self::loadConfig();
        $mysqli = mysqli_init();
        if ($mysqli === false) {
            throw new RuntimeException('PHPTomb: mysqli_init failed.');
        }

        $mysqli->real_connect(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name'],
            (int) $config['port']
        );

        if ($mysqli->connect_error) {
            throw new RuntimeException(
                'PHPTomb: Unable to connect to MySQL: ' . $mysqli->connect_error,
                (int) $mysqli->connect_errno
            );
        }

        $mysqli->set_charset('utf8mb4');
        self::$sharedConnection = $mysqli;

        return self::$sharedConnection;
    }

    /**
     * @return array{host:string,user:string,pass:string,name:string,port:int}
     */
    private static function loadConfig(): array
    {
        $serverFile = dirname(__DIR__) . '/server.php';
        if (is_file($serverFile)) {
            /** @psalm-suppress UnresolvableInclude */
            require_once $serverFile;
        }

        $host = getenv('DB_HOST') ?: ($GLOBALS['SERVER'] ?? 'localhost');
        $port = getenv('DB_PORT') !== false && getenv('DB_PORT') !== ''
            ? (int) getenv('DB_PORT')
            : (int) ($GLOBALS['DB_PORT'] ?? 3306);
        $user = getenv('DB_USER') !== false && getenv('DB_USER') !== ''
            ? getenv('DB_USER')
            : ($GLOBALS['USER'] ?? 'root');
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($GLOBALS['PASS'] ?? '');
        $name = getenv('DB_NAME') !== false && getenv('DB_NAME') !== ''
            ? getenv('DB_NAME')
            : ($GLOBALS['DBNAME'] ?? '');

        return [
            'host' => is_string($host) ? $host : 'localhost',
            'user' => is_string($user) ? $user : 'root',
            'pass' => is_string($pass) ? $pass : '',
            'name' => is_string($name) ? $name : '',
            'port' => $port > 0 ? $port : 3306,
        ];
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public static function create(string $table): self
    {
        return new self($table);
    }

    /** Begin a transaction (requires InnoDB). */
    public static function beginTransaction(): bool
    {
        return self::connection()->begin_transaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollback(): bool
    {
        return self::connection()->rollback();
    }

    /**
     * Run arbitrary SQL using the shared connection (replaces broken static raw()).
     *
     * @return mysqli_result|bool
     */
    public static function raw(string $sql)
    {
        return self::connection()->query($sql);
    }

    /**
     * Close the shared connection (tests, workers, reconnect scenarios).
     */
    public static function resetConnection(): void
    {
        if (self::$sharedConnection !== null) {
            self::$sharedConnection->close();
            self::$sharedConnection = null;
        }
    }

    public function addColumn(
        string $columnName = '',
        string $columnType = 'text',
        int $columnSize = 0,
        string $nullable = 'NOT NULL'
    ): self {
        $col = $this->escapeIdentifier($columnName);
        $this->addQry .= ' `' . $col . '` ' . $columnType;
        if ($columnSize > 0) {
            $this->addQry .= '(' . $columnSize . ') ';
        }
        $this->addQry .= ' ' . $nullable . ', ';
        return $this;
    }

    public function execute()
    {
        $table = $this->escapeIdentifier($this->__table);
        $query = 'CREATE TABLE `' . $table . '` ( `id` INT NOT NULL AUTO_INCREMENT ,'
            . $this->addQry
            . ' `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP , `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`))';
        $this->_log($query);

        return $this->connect->query($query);
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    public function select(string $values): self
    {
        $this->selects = $values;
        return $this;
    }

    private function whereClauseSql(): string
    {
        return $this->where === '' ? '1' : $this->where;
    }

    private function groupBySql(): string
    {
        if ($this->groupByParts === []) {
            return '';
        }
        return ' GROUP BY ' . implode(', ', $this->groupByParts);
    }

    private function orderBySql(): string
    {
        if ($this->orderByParts === []) {
            return '';
        }
        return ' ORDER BY ' . implode(', ', $this->orderByParts);
    }

    private function havingSql(): string
    {
        return $this->having;
    }

    public function generateQuery(): string
    {
        $sel = $this->distinct ? 'DISTINCT ' : '';
        $sel .= $this->selects;
        $q = 'SELECT ' . $sel . ' FROM `' . $this->__table . '` ' . $this->joinQry
            . ' WHERE ' . $this->whereClauseSql()
            . $this->groupBySql()
            . $this->havingSql()
            . $this->orderBySql()
            . $this->limitClause;
        $this->_log($q);

        return $q;
    }

    /**
     * @return list<stdClass>
     */
    public function get(): array
    {
        $query = $this->generateQuery();
        $response = $this->connect->query($query);
        if ($response === false) {
            return [];
        }
        $output = [];
        while ($result = mysqli_fetch_object($response)) {
            $output[] = $result;
        }
        mysqli_free_result($response);

        return $output;
    }

    public function first(): ?\stdClass
    {
        $saved = $this->limitClause;
        $this->limitClause = '';
        $query = $this->generateQuery();
        $this->limitClause = $saved;
        $response = $this->connect->query($query . ' LIMIT 1');
        if ($response === false) {
            return null;
        }
        $row = mysqli_fetch_object($response);
        mysqli_free_result($response);

        return $row !== false ? $row : null;
    }

    public function exists(): bool
    {
        $saved = $this->limitClause;
        $this->limitClause = ' LIMIT 1';
        $query = $this->generateQuery();
        $this->limitClause = $saved;
        $response = $this->connect->query($query);
        if ($response === false) {
            return false;
        }
        $n = mysqli_num_rows($response);
        mysqli_free_result($response);

        return $n > 0;
    }

    public function count(): int
    {
        $w = $this->whereClauseSql();
        $join = $this->joinQry;
        $groupSql = $this->groupBySql();
        $havingSql = $this->havingSql();

        if ($groupSql !== '') {
            $inner = 'SELECT 1 FROM `' . $this->__table . '` ' . $join . ' WHERE ' . $w
                . $groupSql . $havingSql;
            $q = 'SELECT COUNT(*) AS c FROM (' . $inner . ') AS tomb_count_sub';
        } else {
            $q = 'SELECT COUNT(*) AS c FROM `' . $this->__table . '` ' . $join . ' WHERE ' . $w
                . $havingSql;
        }

        $this->_log($q);
        $response = $this->connect->query($q);
        if ($response === false) {
            return 0;
        }
        $row = mysqli_fetch_assoc($response);
        mysqli_free_result($response);

        return isset($row['c']) ? (int) $row['c'] : 0;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values = []): bool
    {
        if ($values === []) {
            return false;
        }

        $cols = [];
        $vals = [];
        foreach ($values as $k => $v) {
            $cols[] = '`' . $this->escapeIdentifier((string) $k) . '`';
            $vals[] = $this->quoteValue($v);
        }

        $sql = 'INSERT INTO `' . $this->__table . '` (' . implode(',', $cols) . ') VALUES ('
            . implode(',', $vals) . ')';
        $this->_log($sql);

        return (bool) $this->connect->query($sql);
    }

    public static function getLastInsertId(): int
    {
        return (int) self::connection()->insert_id;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values = []): bool
    {
        if ($values === [] || $this->where === '') {
            return false;
        }

        $sets = [];
        foreach ($values as $k => $v) {
            $sets[] = '`' . $this->escapeIdentifier((string) $k) . '`=' . $this->quoteValue($v);
        }

        $sql = 'UPDATE `' . $this->__table . '` SET ' . implode(',', $sets)
            . ' WHERE ' . $this->where;
        $this->_log($sql);

        return (bool) $this->connect->query($sql);
    }

    /**
     * DELETE with a WHERE clause. Refuses to run when WHERE is empty (prevents full-table delete by accident).
     */
    public function delete()
    {
        if ($this->where === '') {
            return false;
        }

        $sql = 'DELETE FROM `' . $this->__table . '` WHERE ' . $this->where;
        $this->_log($sql);

        return $this->connect->query($sql);
    }

    /** Unrestricted delete (all rows). Use only when intentional. */
    public function truncateTable()
    {
        $sql = 'TRUNCATE TABLE `' . $this->__table . '`';
        $this->_log($sql);

        return $this->connect->query($sql);
    }

    public function leftJoin(string $table = '', string $field_table1 = '', string $field_table2 = ''): self
    {
        $t = $this->escapeIdentifier($table);
        $this->joinQry .= ' LEFT JOIN `' . $t . '` ON ' . $field_table1 . ' = ' . $field_table2;
        $this->_log($this->joinQry);

        return $this;
    }

    public function innerJoin(string $table = '', string $field_table1 = '', string $field_table2 = ''): self
    {
        $t = $this->escapeIdentifier($table);
        $this->joinQry .= ' INNER JOIN `' . $t . '` ON ' . $field_table1 . ' = ' . $field_table2;
        $this->_log($this->joinQry);

        return $this;
    }

    public function rightJoin(string $table = '', string $field_table1 = '', string $field_table2 = ''): self
    {
        $t = $this->escapeIdentifier($table);
        $this->joinQry .= ' RIGHT JOIN `' . $t . '` ON ' . $field_table1 . ' = ' . $field_table2;
        $this->_log($this->joinQry);

        return $this;
    }

    /**
     * @param string|array<string, mixed> $key
     */
    public function where($key = '', $sec = '', $third = ''): self
    {
        $this->generateWhere($key, $sec, $third, $this->andDeli);

        return $this;
    }

    /**
     * @param string|array<string, mixed> $key
     */
    public function orWhere($key = '', $sec = '', $third = ''): self
    {
        $this->generateWhere($key, $sec, $third, $this->orDeli);

        return $this;
    }

    public function whereNull(string $column): self
    {
        $c = $this->escapeIdentifier($column);
        if ($this->where !== '') {
            $this->where .= $this->andDeli;
        }
        $this->where .= '`' . $c . '` IS NULL';

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $c = $this->escapeIdentifier($column);
        if ($this->where !== '') {
            $this->where .= $this->andDeli;
        }
        $this->where .= '`' . $c . '` IS NOT NULL';

        return $this;
    }

    /**
     * @param list<mixed>|array<int|string, mixed> $array
     */
    public function whereIn(string $field, array $array): self
    {
        if ($array === []) {
            if ($this->where !== '') {
                $this->where .= $this->andDeli;
            }
            $this->where .= '0';

            return $this;
        }
        $parts = [];
        foreach ($array as $v) {
            $parts[] = $this->quoteValue($v);
        }
        $f = $this->escapeIdentifier($field);
        if ($this->where !== '') {
            $this->where .= $this->andDeli;
        }
        $this->where .= '`' . $f . '` IN (' . implode(',', $parts) . ')';

        return $this;
    }

    /**
     * @param list<mixed>|array<int|string, mixed> $array
     */
    public function whereNotIn(string $field, array $array): self
    {
        if ($array === []) {
            return $this;
        }
        $parts = [];
        foreach ($array as $v) {
            $parts[] = $this->quoteValue($v);
        }
        $f = $this->escapeIdentifier($field);
        if ($this->where !== '') {
            $this->where .= $this->andDeli;
        }
        $this->where .= '`' . $f . '` NOT IN (' . implode(',', $parts) . ')';

        return $this;
    }

    public function having(string $clause): self
    {
        $this->having .= ($this->having === '' ? ' HAVING ' : ' AND ') . $clause;

        return $this;
    }

    /**
     * @param string|array<string, mixed> $key
     */
    public function generateWhere($key = '', $sec = '', $third = '', string $deli = ''): string
    {
        if (is_array($key)) {
            if ($this->where !== '') {
                $this->where .= ' ' . $deli;
            }
            $this->where .= $this->parseArray($key, $deli);
        } else {
            $exp = '=';
            if ($third === '') {
                $value = $sec;
            } else {
                $exp = (string) $sec;
                $value = $third;
            }
            if ($this->where !== '') {
                $this->where .= ' ' . $deli;
            }
            $k = $this->escapeIdentifier((string) $key);
            $expUpper = strtoupper(trim($exp));
            if ($expUpper === 'LIKE') {
                $this->where .= '`' . $k . '` LIKE ' . $this->quoteValue('%' . (string) $value . '%');
            } else {
                $this->where .= '`' . $k . '`' . $exp . $this->quoteValue($value);
            }
        }
        $this->_log($this->where);

        return $this->where;
    }

    public function limit(int $from, int $count): self
    {
        $this->limitClause = ' LIMIT ' . $from . ',' . $count;

        return $this;
    }

    public function orderBy(string $column = 'id', string $order = 'ASC'): self
    {
        $ord = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderByParts[] = $this->qualifyColumn($column) . ' ' . $ord;

        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->groupByParts[] = $this->qualifyColumn($column);

        return $this;
    }

    /**
     * @param array<string, mixed> $array
     */
    public function parseArray(array $array = [], string $delimeter = ''): string
    {
        $parts = [];
        $sr = 0;
        $limit = count($array);
        foreach ($array as $k => $a) {
            $sr++;
            $key = $this->escapeIdentifier((string) $k);
            $suffix = ($sr === $limit) ? '' : (' ' . $delimeter);
            $parts[] = '`' . $key . '`=' . $this->quoteValue($a) . $suffix;
        }

        $result = implode('', $parts);
        $this->_log($result);

        return $result;
    }

    public function _log(string $result): void
    {
        if (!defined('TOMB_LOG_ENABLED') || TOMB_LOG_ENABLED !== true) {
            return;
        }
        $dir = dirname(__DIR__) . '/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir . '/tomb_log_' . date('j.n.Y') . '.txt';
        file_put_contents($file, date('Y-m-d H:i:s') . ':' . $result . "\n", FILE_APPEND);
    }

    private function escapeIdentifier(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', $name);

        return $clean !== '' ? $clean : 'invalid_identifier';
    }

    /** Backtick-quoted column or `table`.`column` reference. */
    private function qualifyColumn(string $name): string
    {
        $segments = explode('.', $name);
        $chunks = [];
        foreach ($segments as $seg) {
            $chunks[] = '`' . $this->escapeIdentifier($seg) . '`';
        }

        return implode('.', $chunks);
    }

    /**
     * @param mixed $value
     */
    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '"' . $this->connect->real_escape_string((string) $value) . '"';
    }

}
