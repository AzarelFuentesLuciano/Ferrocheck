<?php
declare(strict_types=1);namespace App\Repositories\ControlEscaneres\Contracts;use App\DTO\ControlEscaneres\ScannerCatalogFilter;
interface ScannerCatalogQueryInterface{public function search(ScannerCatalogFilter$filter):array;public function count(ScannerCatalogFilter$filter):int;}
