<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\Scanner;final readonly class HistoryResult{public function __construct(public Scanner$scanner,public array$timeline,public array$movements,public array$inspections,public array$incidents,public array$evidences,public array$auditEvents){}}
