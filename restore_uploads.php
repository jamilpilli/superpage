<?php
// Script temporário de restore — APAGAR APÓS USO
// Acesso protegido por token

define('RESTORE_TOKEN', 'sp_restore_2026_x9k');

if (($_GET['token'] ?? '') !== RESTORE_TOKEN) {
    http_response_code(403);
    die('Forbidden');
}

$uploadBase = __DIR__ . '/uploads';

// Upload de ficheiro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $relativePath = $_POST['path'] ?? '';

    // Segurança: só permite paths dentro de uploads/users/
    if (!preg_match('#^users/\d+/[a-f0-9]+\.(webp|jpg|png)$#', $relativePath)) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid path']));
    }

    $destPath = $uploadBase . '/' . $relativePath;
    $destDir  = dirname($destPath);

    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }

    if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
        echo json_encode(['ok' => true, 'path' => $relativePath]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
    }
    exit;
}

// Listagem dos ficheiros já existentes
$existing = [];
$dir = $uploadBase . '/users';
if (is_dir($dir)) {
    foreach (glob($dir . '/*/*') as $f) {
        $existing[] = str_replace($uploadBase . '/', '', $f);
    }
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ready', 'existing' => $existing]);
