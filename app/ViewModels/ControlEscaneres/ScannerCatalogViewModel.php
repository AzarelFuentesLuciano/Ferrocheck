<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;
final readonly class ScannerCatalogViewModel{public function __construct(public array$items,public int$total,public int$page,public int$perPage,public int$totalPages,public array$filters,public array$flashMessages=[],public?string$error=null){foreach($items as$i)if(!$i instanceof ScannerCatalogItemViewModel)throw new\InvalidArgumentException('Item invalido.');}}
