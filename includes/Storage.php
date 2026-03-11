<?php
// includes/Storage.php
// Abstração de armazenamento para Uploads

interface StorageDriver {
    /**
     * Salva um arquivo no disco ou nuvem
     * @param string $sourcePath Caminho absoluto do arquivo temporário atual
     * @param string $destinationPath Caminho relativo de destino (ex: 'sites/1/image.webp')
     * @return bool
     */
    public function save(string $sourcePath, string $destinationPath): bool;

    /**
     * Remove um arquivo
     * @param string $path Caminho relativo
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * Retorna a URL pública para acessar o arquivo
     * @param string $path Caminho relativo
     * @return string
     */
    public function getUrl(string $path): string;
}

class LocalStorage implements StorageDriver {
    private string $baseDir;
    private string $baseUrl;

    public function __construct() {
        $this->baseDir = defined('UPLOAD_DIR') ? rtrim(UPLOAD_DIR, '/') : __DIR__ . '/../uploads';
        $this->baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/uploads' : '/uploads';
        
        // Garante que a pasta root de uploads exista
        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }
    }

    public function save(string $sourcePath, string $destinationPath): bool {
        $fullPath = $this->baseDir . '/' . ltrim($destinationPath, '/');
        $dir = dirname($fullPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Move ou copia o arquivo
        if (is_uploaded_file($sourcePath)) {
            return move_uploaded_file($sourcePath, $fullPath);
        } else {
            return rename($sourcePath, $fullPath);
        }
    }

    public function delete(string $path): bool {
        $fullPath = $this->baseDir . '/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }

    public function getUrl(string $path): string {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
}

// Factory Pattern simples para o driver atual
class Storage {
    private static ?StorageDriver $instance = null;

    public static function disk(): StorageDriver {
        if (self::$instance === null) {
            // Em fases futuras, podemos ler do .env 'STORAGE_DRIVER=s3' e instanciar aqui
            self::$instance = new LocalStorage();
        }
        return self::$instance;
    }
}
