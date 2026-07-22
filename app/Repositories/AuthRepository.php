<?php
declare(strict_types=1);
namespace App\Repositories;

final class AuthRepository
{
    public function __construct(private \PDO $pdo) {}

    public function findForLogin(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id,nombre,usuario,password_hash,activo FROM usuarios WHERE usuario=:usuario LIMIT 1');
        $stmt->execute(['usuario' => $username]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id,nombre,usuario FROM usuarios WHERE id=:id AND activo=1 LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function rolesAndPermissions(int $userId): array
    {
        $roles = $this->pdo->prepare('SELECT r.nombre FROM roles r JOIN usuario_roles ur ON ur.rol_id=r.id WHERE ur.usuario_id=:id AND r.activo=1 ORDER BY r.nombre');
        $roles->execute(['id' => $userId]);
        $permissions = $this->pdo->prepare('SELECT DISTINCT p.clave FROM permisos p JOIN rol_permisos rp ON rp.permiso_id=p.id JOIN roles r ON r.id=rp.rol_id JOIN usuario_roles ur ON ur.rol_id=r.id WHERE ur.usuario_id=:id AND r.activo=1 ORDER BY p.clave');
        $permissions->execute(['id' => $userId]);
        return [$roles->fetchAll(\PDO::FETCH_COLUMN), $permissions->fetchAll(\PDO::FETCH_COLUMN)];
    }

    public function touchLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET ultimo_acceso=CURRENT_TIMESTAMP(6) WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    public function createSession(int $userId, string $hash, ?string $ip, ?string $userAgentHash, \DateTimeImmutable $expires): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuario_sesiones(usuario_id,session_hash,ip,user_agent_hash,expira_at)VALUES(:usuario,:hash,:ip,:ua,:expira)');
        $stmt->execute(['usuario'=>$userId,'hash'=>$hash,'ip'=>$ip,'ua'=>$userAgentHash,'expira'=>$expires->format('Y-m-d H:i:s.u')]);
    }

    public function validSession(int $userId, string $hash): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuario_sesiones WHERE usuario_id=:usuario AND session_hash=:hash AND revocada_at IS NULL AND expira_at>CURRENT_TIMESTAMP(6) LIMIT 1');
        $stmt->execute(['usuario'=>$userId,'hash'=>$hash]);
        return $stmt->fetchColumn() !== false;
    }

    public function touchSession(string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuario_sesiones SET ultimo_uso=CURRENT_TIMESTAMP(6) WHERE session_hash=:hash');
        $stmt->execute(['hash'=>$hash]);
    }

    public function revokeSession(string $hash, ?int $actorId, string $reason): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuario_sesiones SET revocada_at=CURRENT_TIMESTAMP(6),revocada_por=:actor,motivo_revocacion=:motivo WHERE session_hash=:hash AND revocada_at IS NULL');
        $stmt->execute(['actor'=>$actorId,'motivo'=>$reason,'hash'=>$hash]);
    }

    public function revokeAll(int $userId, int $actorId, string $reason): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuario_sesiones SET revocada_at=CURRENT_TIMESTAMP(6),revocada_por=:actor,motivo_revocacion=:motivo WHERE usuario_id=:usuario AND revocada_at IS NULL');
        $stmt->execute(['actor'=>$actorId,'motivo'=>$reason,'usuario'=>$userId]);
    }
}
