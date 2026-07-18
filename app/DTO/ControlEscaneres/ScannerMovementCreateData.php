<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres; use App\Domain\ControlEscaneres\ScannerFolio;
final readonly class ScannerMovementCreateData {public function __construct(public int $scannerId,public ScannerFolio $folio,public string $personName,public string $employeeNumber,public string $shift,public \DateTimeImmutable $deliveredAt,public AuthenticatedActorData $actor){}}
