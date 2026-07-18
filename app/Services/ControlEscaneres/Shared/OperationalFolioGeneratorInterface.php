<?php
declare(strict_types=1);namespace App\Services\ControlEscaneres\Shared;use App\Domain\ControlEscaneres\ScannerFolio;interface OperationalFolioGeneratorInterface{public function generate():ScannerFolio;}
