<?php
declare(strict_types=1); namespace App\Repositories\ControlEscaneres\Contracts;
use App\DTO\ControlEscaneres\AuditEventData;use App\Domain\ControlEscaneres\AuditEvent;
interface AuditRepositoryInterface {public function append(AuditEventData $d):AuditEvent;}
