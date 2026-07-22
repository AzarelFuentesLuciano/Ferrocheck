<?php
declare(strict_types=1);
namespace App\Repositories\ControlEscaneres\Contracts;
use App\Domain\ControlEscaneres\Scanner;
interface ScannerHistoryQueryInterface{public function getScannerSummary(int$id):?Scanner;public function getScannerDetails(int$id):?array;public function listMovements(int$id):array;public function listInspections(int$id):array;public function listInspectionDetails(int$id):array;public function listIncidents(int$id):array;public function listEvidences(int$id):array;public function listDifferences(int$id):array;public function listMaintenance(int$id):array;public function listIncidentFollowUps(int$id):array;public function buildTimeline(int$id):array;}
