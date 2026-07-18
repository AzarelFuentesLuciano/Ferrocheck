<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres;
final readonly class ScannerEvidenceMetadata {public function __construct(public int $scannerId,public string $type,public string $storagePath,public string $mimeType,public int $sizeBytes,public string $sha256,public \DateTimeImmutable $capturedAt,public AuthenticatedActorData $actor){if(!preg_match('/^[a-f0-9]{64}$/i',$sha256)||$sizeBytes<0)throw new \InvalidArgumentException('Evidencia inválida.');}}
