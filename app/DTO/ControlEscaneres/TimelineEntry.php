<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class TimelineEntry{public function __construct(public string$type,public string$title,public \DateTimeImmutable$occurredAt,public?int$entityId=null,public array$metadata=[]){}}
