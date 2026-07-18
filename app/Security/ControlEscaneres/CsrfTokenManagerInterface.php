<?php
declare(strict_types=1);
namespace App\Security\ControlEscaneres;
interface CsrfTokenManagerInterface { public function token(): string; public function validate(string $token): bool; public function rotate(): string; }
