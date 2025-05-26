<?php
function dbQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetch($sql, $params = []) {
    return dbQuery($sql, $params)->fetch();
}

function dbFetchAll($sql, $params = []) {
    return dbQuery($sql, $params)->fetchAll();
}

function dbInsert($table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    dbQuery($sql, array_values($data));
    return $pdo->lastInsertId();
}

function dbUpdate($table, $data, $where, $params = []) {
    $set = implode(' = ?, ', array_keys($data)) . ' = ?';
    $sql = "UPDATE $table SET $set WHERE $where";
    return dbQuery($sql, array_merge(array_values($data), $params))->rowCount();
}