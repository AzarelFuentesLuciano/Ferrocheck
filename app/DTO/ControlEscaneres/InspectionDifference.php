<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class InspectionDifference{public function __construct(public string$component,public mixed$before,public mixed$after,public string$result){}}
