<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class ScannerAttentionItem{public function __construct(public int$scannerId,public string$scannerCode,public string$situation,public string$severity,public \DateTimeImmutable$occurredAt){}}
