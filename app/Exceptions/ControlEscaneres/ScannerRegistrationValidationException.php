<?php
declare(strict_types=1);
namespace App\Exceptions\ControlEscaneres;
final class ScannerRegistrationValidationException extends \InvalidArgumentException
{
    public function __construct(public readonly array $errors,public readonly array $values){parent::__construct('Revisa los campos marcados antes de registrar el escáner.');}
}
