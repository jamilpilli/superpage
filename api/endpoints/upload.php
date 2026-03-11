<?php
// api/endpoints/upload.php
// Responsável por receber imgs, varrer validações de segurança e converter para WebP

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/Storage.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado.']);
    exit;
}
$user = get_logged_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método inválido.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum arquivo válido enviado.']);
    exit;
}

$file = $_FILES['image'];
$fileName = $file['name'];
$fileSize = $file['size'];
$fileTmp = $file['tmp_name'];

// 1. Validar Tamanho (Max definido em config/app.php, default 5MB)
if ($fileSize > MAX_UPLOAD_SIZE) {
    http_response_code(400);
    echo json_encode(['error' => 'O arquivo não deve ultrapassar 5MB.']);
    exit;
}

// 2. Validar Extensão Mimetype (Segurança contra PHP injetado/XSS via File)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fileTmp);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de imagem não suportado. Use JPG, PNG, GIF ou WEBP.']);
    exit;
}

// 3. Pipeline de Otimização (GD) - processAndSaveImage() lógica inline p/ Endpoint isolado
if (!extension_loaded('gd')) {
    http_response_code(500);
    echo json_encode(['error' => 'Extensão GD do servidor não está ativada. Peça suporte ao Adm.']);
    exit;
}

try {
    // Ler Imagem da Mémória
    $imageMemory = null;
    switch ($mime) {
        case 'image/jpeg': $imageMemory = imagecreatefromjpeg($fileTmp); break;
        case 'image/png': $imageMemory = imagecreatefrompng($fileTmp); break;
        case 'image/gif': $imageMemory = imagecreatefromgif($fileTmp); break;
        case 'image/webp': $imageMemory = imagecreatefromwebp($fileTmp); break;
    }

    if (!$imageMemory) {
        throw new Exception("Falha ao ler a imagem na memória.");
    }

    // Orientação e Resizing (Opcional - mas garante peso leve pros sites)
    $origWidth = imagesx($imageMemory);
    $origHeight = imagesy($imageMemory);
    
    // Limits de acordo com a Orientação 
    $isLandscape = $origWidth > $origHeight;
    $maxWidth = $isLandscape ? 1920 : 800; // Paisagem grande vs Retrato
    
    $newWidth = $origWidth;
    $newHeight = $origHeight;

    if ($origWidth > $maxWidth) {
        $ratio = $maxWidth / $origWidth;
        $newWidth = $maxWidth;
        $newHeight = (int)($origHeight * $ratio);
        
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparência pro PNG/WEBP no resize inicial
        if ($mime == 'image/png' || $mime == 'image/webp') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $imageMemory, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagedestroy($imageMemory);
        $imageMemory = $resizedImage;
    }

    // 4. Fundo branco para PNG/WEBP quando o servidor não suporta WebP
    $canWebp = function_exists('imagewebp');
    $ext = $canWebp ? 'webp' : 'jpg';

    if (!$canWebp && in_array($mime, ['image/png', 'image/webp'])) {
        $bg = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefill($bg, 0, 0, $white);
        imagecopy($bg, $imageMemory, 0, 0, 0, 0, $newWidth, $newHeight);
        imagedestroy($imageMemory);
        $imageMemory = $bg;
    }

    // 5. Salvar DIRETAMENTE no destino final (evita problemas de tmp + extensão)
    $uid = $user['id'];
    $randId = substr(md5(uniqid((string)rand(), true)), 0, 10);
    $finalPathRelative = "users/{$uid}/{$randId}.{$ext}";
    
    $uploadBase = defined('UPLOAD_DIR') ? rtrim(UPLOAD_DIR, '/') : __DIR__ . '/../../uploads';
    $destDir  = $uploadBase . "/users/{$uid}";
    $destFull = $uploadBase . '/' . ltrim($finalPathRelative, '/');

    // Cria o diretório se não existir
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0775, true)) {
            throw new Exception("Não foi possível criar o diretório de destino: {$destDir}");
        }
    }

    // Verifica se o diretório é gravável
    if (!is_writable($destDir)) {
        throw new Exception("Diretório de destino sem permissão de escrita: {$destDir}");
    }

    // Salvar usando GD diretamente no caminho final
    $saved = $canWebp
        ? imagewebp($imageMemory, $destFull, 80)
        : imagejpeg($imageMemory, $destFull, 85);

    imagedestroy($imageMemory);

    if (!$saved || !file_exists($destFull)) {
        throw new Exception("Falha ao gravar a imagem no disco (" . strtoupper($ext) . "). Verifique permissões em: {$destDir}");
    }

    // 6. Resposta de sucesso
    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $publicUrl = $baseUrl . '/uploads/' . ltrim($finalPathRelative, '/');

    echo json_encode([
        'success' => true,
        'url'     => $publicUrl,
        'path'    => $finalPathRelative,
        'message' => 'Imagem processada e salva com sucesso!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao processar a imagem: ' . $e->getMessage()]);
}
