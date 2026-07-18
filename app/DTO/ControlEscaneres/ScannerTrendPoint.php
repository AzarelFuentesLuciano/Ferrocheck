<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class ScannerTrendPoint{public function __construct(public string$date,public int$deliveries,public int$receptions,public int$incidents){}}
