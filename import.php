<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'superpage';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    $sql = file_get_contents(__DIR__ . '/dump_data.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $ok = 0; $errors = [];
    foreach ($statements as $stmt) {
        if (!$stmt || str_starts_with($stmt, '--') || str_starts_with($stmt, '/*')) continue;
        try { $pdo->exec($stmt); $ok++; } catch (Exception $e) { $errors[] = $e->getMessage(); }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $users = $pdo->query("SELECT id, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $sites = $pdo->query("SELECT id, slug, user_id FROM sites")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('<pre style="color:red">ERRO: ' . $e->getMessage() . '</pre>');
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Import</title>
<style>body{font-family:monospace;background:#0d0d1a;color:#e9e6f9;padding:40px;max-width:700px;margin:0 auto}table{border-collapse:collapse;width:100%}td,th{padding:8px 12px;border:1px solid rgba(255,255,255,0.1);text-align:left}th{color:#a9a4ff}</style>
</head><body>
<h2>✅ Import concluído — <?= $ok ?> statements</h2>
<?php if($errors): ?><h3 style="color:#ff6e84">Erros (<?= count($errors) ?>)</h3><ul><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul><?php endif; ?>
<h3>Users</h3><table><tr><th>id</th><th>email</th><th>role</th></tr><?php foreach($users as $u): ?><tr><td><?=$u['id']?></td><td><?=$u['email']?></td><td><?=$u['role']?></td></tr><?php endforeach; ?></table>
<h3>Sites</h3><table><tr><th>id</th><th>slug</th><th>user_id</th></tr><?php foreach($sites as $s): ?><tr><td><?=$s['id']?></td><td><?=$s['slug']?></td><td><?=$s['user_id']?></td></tr><?php endforeach; ?></table>
<p style="color:#ff98cd;margin-top:40px">⚠️ APAGA o ficheiro import.php agora!</p>
</body></html>
