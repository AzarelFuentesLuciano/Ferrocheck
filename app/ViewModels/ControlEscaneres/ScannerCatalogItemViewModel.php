<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;
final readonly class ScannerCatalogItemViewModel{public function __construct(public int$id,public string$code,public string$brand,public string$model,public string$serial,public string$maskedImei,public string$maskedPhone,public string$maskedIccid,public string$status,public bool$active,public?string$lastDelivery,public bool$hasOpenIncident,public array$actions,public?string$tag=null,public?string$area=null,public?int$conservation=null,public?string$currentResponsible=null,public?string$organizationalArea=null){}}
