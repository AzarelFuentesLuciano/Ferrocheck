<?php
declare(strict_types=1);namespace App\Services\ControlEscaneres\Shared;use App\Domain\ControlEscaneres\ScannerStatus;interface ScannerStateMachineInterface{public function canTransition(ScannerStatus$from,ScannerStatus$to):bool;public function assertTransition(ScannerStatus$from,ScannerStatus$to):void;public function allowedTransitionsFrom(ScannerStatus$from):array;}
