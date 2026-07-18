<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class DashboardKpiViewModel{public function __construct(public string$key,public string$label,public int$value,public string$context,public string$tone,public?string$url=null){}}
