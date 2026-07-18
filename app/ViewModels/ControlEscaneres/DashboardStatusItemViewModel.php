<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class DashboardStatusItemViewModel{public function __construct(public string$label,public int$count,public float$percentage,public string$status){}}
