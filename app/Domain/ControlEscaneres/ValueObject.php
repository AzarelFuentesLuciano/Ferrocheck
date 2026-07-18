<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
abstract readonly class ValueObject { final public function __toString(): string{return (string)$this->value;} }
