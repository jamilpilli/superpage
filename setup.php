<?php
// APAGAR ESTE FICHEIRO DEPOIS DE USAR
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'superpage';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");

    $migrations = glob(__DIR__ . '/migrations/*.sql');
    sort($migrations);

    $results = [];
    foreach ($migrations as $file) {
        $sql = file_get_contents($file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if ($stmt) $pdo->exec($stmt);
        }
        $results[] = '✅ ' . basename($file);
    }

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    die('<pre style="color:red">ERRO: ' . $e->getMessage() . '</pre>');
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Setup</title>
<style>body{font-family:monospace;background:#0d0d1a;color:#e9e6f9;padding:40px;max-width:600px;margin:0 auto}</style>
</head>
<body>
<h2>✅ Migrations executadas</h2>
<ul><?php foreach($results as $r) echo "<li>$r</li>"; ?></ul>
<h2>Tabelas criadas</h2>
<ul><?php foreach($tables as $t) echo "<li>$t</li>"; ?></ul>
<p style="color:#ff98cd;margin-top:40px">⚠️ APAGA o ficheiro setup.php agora!</p>
</body>
</html>
