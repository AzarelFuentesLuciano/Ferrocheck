<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres; final readonly class Scanner {public function __construct(public int $id,public ScannerCode $code,public ScannerStatus $status,public bool $active){}}
