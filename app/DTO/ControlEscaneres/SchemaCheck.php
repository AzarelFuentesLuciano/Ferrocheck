<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class SchemaCheck{public function __construct(public string$name,public string$status,public string$message){if(!in_array($status,['PASS','WARN','BLOCKED'],true))throw new \InvalidArgumentException('Estado inválido.');}}
