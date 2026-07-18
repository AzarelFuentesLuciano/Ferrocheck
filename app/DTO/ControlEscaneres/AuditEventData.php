<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;
final readonly class AuditEventData{public function __construct(public?AuthenticatedActorData$actor,public string$action,public string$module,public string$entity,public string$result,public \DateTimeImmutable$createdAt,public?int$entityId=null,public?array$previousValues=null,public?array$newValues=null,public?array$metadata=null,public?string$requestId=null,public?string$sessionFingerprint=null,public?string$ipAddress=null){}}
