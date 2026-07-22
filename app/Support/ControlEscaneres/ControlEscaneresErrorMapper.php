<?php
declare(strict_types=1);
namespace App\Support\ControlEscaneres;
use App\Exceptions\ControlEscaneres\{DuplicateScannerCodeException,DuplicateScannerIdentityException,InvalidBusinessActorException,InvalidReceptionDateException,InvalidScannerStatusException,OpenMovementAlreadyExistsException,OpenMovementNotFoundException,ScannerNotFoundException,ScannerUnavailableException};
final class ControlEscaneresErrorMapper
{
    public function message(\Throwable$e):string{return match(true){$e instanceof InvalidBusinessActorException=>'Debe iniciar sesión para realizar esta operación.',$e instanceof DuplicateScannerCodeException=>'El código capturado ya está registrado.',$e instanceof DuplicateScannerIdentityException&&str_contains($e->getMessage(),'TAG')=>'El TAG capturado ya está registrado.',$e instanceof DuplicateScannerIdentityException=>'La serie, IMEI o ICCID ya pertenece a otro escáner.',$e instanceof ScannerNotFoundException=>'No se encontró el escáner solicitado.',$e instanceof ScannerUnavailableException=>'El escáner no está disponible.',$e instanceof OpenMovementAlreadyExistsException=>'El escáner ya se encuentra entregado.',$e instanceof OpenMovementNotFoundException=>'No existe un movimiento abierto para este equipo.',$e instanceof InvalidReceptionDateException=>'La recepción no puede registrarse antes de la entrega.',$e instanceof InvalidScannerStatusException=>'La transición de estado solicitada no está permitida.',default=>'No fue posible completar la operacion.'};}
}
