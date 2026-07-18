<?php
declare(strict_types=1);
namespace App\Security\ControlEscaneres;
use App\DTO\ControlEscaneres\AuthenticatedActorData;
use App\Exceptions\ControlEscaneres\InvalidBusinessActorException;
final class SessionAuthenticatedActorProvider implements AuthenticatedActorProviderInterface
{
    public function __construct(private array $session, private ?string $ipAddress = null) {}
    public function getActor(): AuthenticatedActorData
    {
        $id = filter_var($this->session['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) throw new InvalidBusinessActorException('No existe una sesion autenticada para realizar la operacion.');
        $sessionId = session_id();
        if ($sessionId === '') throw new InvalidBusinessActorException('La sesion autenticada no esta disponible.');
        return new AuthenticatedActorData($id, hash('sha256', $sessionId), $this->normalizeIp($this->ipAddress));
    }
    private function normalizeIp(?string $ip): ?string { return $ip !== null && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null; }
}
