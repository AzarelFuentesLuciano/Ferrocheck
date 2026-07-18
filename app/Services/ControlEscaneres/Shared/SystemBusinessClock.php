<?php
declare(strict_types=1);namespace App\Services\ControlEscaneres\Shared;final class SystemBusinessClock implements BusinessClockInterface{public function now():\DateTimeImmutable{return new \DateTimeImmutable();}}
