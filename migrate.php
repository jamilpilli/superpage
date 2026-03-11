<?php
// Script de execução de migrations

require_once __DIR__ . '/config/database.php';

// Criar tabela de controle de migrations se não existir
$pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$executedMigrations = $pdo->query("SELECT migration_name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

$migrationFiles = glob(__DIR__ . '/migrations/*.sql');
sort($migrationFiles);

echo "Iniciando migrações...\n";

foreach ($migrationFiles as $file) {
    $migrationName = basename($file);
    
    if (!in_array($migrationName, $executedMigrations)) {
        echo "Executando: $migrationName ... ";
        
        $sql = file_get_contents($file);
        
        try {
            $pdo->exec($sql);
            
            $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (:name)");
            $stmt->execute([':name' => $migrationName]);
            
            echo "OK\n";
        } catch (Exception $e) {
            echo "FALHA: " . $e->getMessage() . "\n";
            die("Execução interrompida.\n");
        }
    }
}

echo "Geração de banco de dados concluída.\n";
