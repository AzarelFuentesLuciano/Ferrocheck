<?php
declare(strict_types=1); namespace App\Repositories\ControlEscaneres\Contracts;
use App\DTO\ControlEscaneres\ScannerIncidentCreateData;use App\Domain\ControlEscaneres\{ScannerIncident,IncidentStatus,IncidentSeverity};
interface ScannerIncidentRepositoryInterface {public function create(ScannerIncidentCreateData $d):ScannerIncident;public function findById(int $id):?ScannerIncident;public function listByScannerId(int $id):array;public function listOpen():array;public function changeStatus(int $id,IncidentStatus $s,int $actor):void;public function resolve(int $id,string $resolution,int $actor,\DateTimeImmutable $at):void;public function countOpenBySeverity(IncidentSeverity $s):int;}
