<?php
declare(strict_types=1); namespace App\Repositories\ControlEscaneres\Contracts;
use App\DTO\ControlEscaneres\{ScannerInspectionCreateData,ScannerInspectionDetailData};use App\Domain\ControlEscaneres\{ScannerInspection,InspectionType};
interface ScannerInspectionRepositoryInterface {public function createInspection(ScannerInspectionCreateData $d):ScannerInspection;public function addDetail(int $id,ScannerInspectionDetailData $d):void;public function findByMovementAndType(int $id,InspectionType $t):?ScannerInspection;public function listDetails(int $id):array;public function compareDeliveryAndReturn(int $movementId):array;}
