<?php
// Helpers de Banco de Dados (CRUD e Queries)

function db_fetch_all($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_fetch_one($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function db_insert($table, $data) {
    global $pdo;
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_map(function($key) { return ":$key"; }, array_keys($data)));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    return false;
}

function db_update($table, $data, $where, $whereParams = []) {
    global $pdo;
    
    $setClause = implode(', ', array_map(function(string $key) { return "$key = :$key"; }, array_keys($data)));
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    $stmt = $pdo->prepare($sql);
    
    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    foreach ($whereParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    return $stmt->execute();
}
