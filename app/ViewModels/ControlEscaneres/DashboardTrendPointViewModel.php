<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class DashboardTrendPointViewModel{public function __construct(public string$date,public string$label,public int$deliveries,public int$receptions,public int$incidents){}}
