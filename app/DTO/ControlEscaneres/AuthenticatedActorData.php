<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres;
final readonly class AuthenticatedActorData {public function __construct(public int $userId,public string $sessionFingerprint,public ?string $ip=null){if($userId<1)throw new \InvalidArgumentException('Actor inválido.');}}
