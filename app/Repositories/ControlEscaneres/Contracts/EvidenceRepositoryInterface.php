<?php
declare(strict_types=1);namespace App\Repositories\ControlEscaneres\Contracts;use App\DTO\ControlEscaneres\ScannerEvidenceMetadata;
interface EvidenceRepositoryInterface{public function create(ScannerEvidenceMetadata$d):int;public function findById(int$id):?ScannerEvidenceMetadata;public function listByScannerId(int$id):array;public function listByMovementId(int$id):array;public function listByInspectionId(int$id):array;public function listByIncidentId(int$id):array;public function deactivate(int$id,int$actor):void;}
