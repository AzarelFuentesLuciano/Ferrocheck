<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres;
final readonly class AuditEventData {public function __construct(public ?AuthenticatedActorData $actor,public string $action,public string $module,public string $entity,public string $result,public \DateTimeImmutable $occurredAt,public ?int $entityId=null,public ?array $before=null,public ?array $after=null,public ?array $metadata=null){}}
