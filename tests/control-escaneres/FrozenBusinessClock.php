<?php
declare(strict_types=1);namespace Tests\ControlEscaneres;use App\Services\ControlEscaneres\Shared\BusinessClockInterface;final class FrozenBusinessClock implements BusinessClockInterface{public function __construct(private \DateTimeImmutable$time){}public function now():\DateTimeImmutable{return$this->time;}}
