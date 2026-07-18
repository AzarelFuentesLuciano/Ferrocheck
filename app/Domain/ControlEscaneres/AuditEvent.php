<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres; final readonly class AuditEvent {public function __construct(public int $id,public string $action,public string $result){}}
