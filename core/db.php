<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function db_one(string $sql, array $params = []): ?array {
    return db_query($sql, $params)->fetch() ?: null;
}

function db_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

function db_count(string $sql, array $params = []): int {
    return (int) db_query($sql, $params)->fetchColumn();
}

function db_insert(string $table, array $data): int {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $phs  = implode(', ', array_fill(0, count($data), '?'));
    db_query("INSERT INTO `$table` ($cols) VALUES ($phs)", array_values($data));
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $where_params = []): void {
    $set = implode(', ', array_map(fn($k) => "`$k`=?", array_keys($data)));
    db_query("UPDATE `$table` SET $set WHERE $where",
             array_merge(array_values($data), $where_params));
}

function db_delete(string $table, string $where, array $params = []): void {
    db_query("DELETE FROM `$table` WHERE $where", $params);
}
