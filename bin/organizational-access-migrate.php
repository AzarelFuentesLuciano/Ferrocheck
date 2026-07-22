<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/config.php';

use App\Core\Database;

$args = array_slice($argv, 1);
$value = static function (string $name) use ($args): ?string {
    foreach ($args as $arg) if (str_starts_with($arg, $name . '=')) return substr($arg, strlen($name) + 1);
    return null;
};

$execute = in_array('--execute', $args, true);
$confirmed = in_array('--confirm-local', $args, true);
$backupPath = $value('--backup');
$backupChecksum = $value('--backup-checksum');
$root = dirname(__DIR__);
$migrationName = '20260722_013_prepare_organizational_access.sql';
$migrationPath = $root . '/database/migrations/' . $migrationName;

try {
    if (!$execute || !$confirmed) throw new DomainException('Se requieren --execute y --confirm-local.');
    if (!in_array(DB_HOST, ['localhost','127.0.0.1','::1'], true) || DB_NAME !== 'ferrocheck') throw new DomainException('Sólo se permite la base local ferrocheck.');
    if (!is_string($backupPath) || !is_file($backupPath) || filesize($backupPath) <= 0) throw new DomainException('Respaldo previo ausente o vacío.');
    if (!is_string($backupChecksum) || !hash_equals(strtolower($backupChecksum), strtolower((string) hash_file('sha256', $backupPath)))) throw new DomainException('Checksum del respaldo inválido.');
    $sql = file_get_contents($migrationPath);
    if (!is_string($sql) || $sql === '') throw new DomainException('Migración vacía.');
    $checksum = hash('sha256', $sql);
    $pdo = Database::getConnection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,module VARCHAR(80) NOT NULL,migration_name VARCHAR(190) NOT NULL,checksum CHAR(64) NOT NULL,applied_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),execution_id VARCHAR(64) NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_schema_migrations_module_name(module,migration_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $check = $pdo->prepare('SELECT checksum FROM schema_migrations WHERE module=:module AND migration_name=:name');
    $check->execute(['module'=>'organizational-access','name'=>$migrationName]);
    $applied = $check->fetchColumn();
    if ($applied !== false) {
        if (!hash_equals($checksum, (string) $applied)) throw new DomainException('La migración registrada tiene otro checksum.');
        echo '[SKIP] Migración ya aplicada y checksum válido.' . PHP_EOL;
        exit(0);
    }
    $executionId = bin2hex(random_bytes(16));
    $pdo->exec($sql);
    $record = $pdo->prepare('INSERT INTO schema_migrations(module,migration_name,checksum,execution_id) VALUES(:module,:name,:checksum,:execution)');
    $record->execute(['module'=>'organizational-access','name'=>$migrationName,'checksum'=>$checksum,'execution'=>$executionId]);
    echo '[APPLIED] ' . $migrationName . ' checksum=' . $checksum . ' execution_id=' . $executionId . PHP_EOL;
} catch (DomainException $exception) {
    fwrite(STDERR, '[BLOCKED] ' . $exception->getMessage() . PHP_EOL);
    exit(2);
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] Aplicación detenida: ' . get_class($exception) . PHP_EOL);
    exit(3);
}
