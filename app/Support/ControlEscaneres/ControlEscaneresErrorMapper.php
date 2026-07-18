<?php
declare(strict_types=1);
namespace App\Support\ControlEscaneres;
use App\Exceptions\ControlEscaneres\{InvalidBusinessActorException,InvalidReceptionDateException,InvalidScannerStatusException,OpenMovementAlreadyExistsException,OpenMovementNotFoundException,ScannerNotFoundException,ScannerUnavailableException};
final class ControlEscaneresErrorMapper
{
    public function message(\Throwable$e):string{return match(true){$e instanceof InvalidBusinessActorException=>'Debe iniciar sesion para realizar esta operacion.',$e instanceof ScannerNotFoundException=>'No se encontro el escaner solicitado.',$e instanceof ScannerUnavailableException=>'El escaner no esta disponible.',$e instanceof OpenMovementAlreadyExistsException=>'El escaner ya se encuentra entregado.',$e instanceof OpenMovementNotFoundException=>'No existe un movimiento abierto para este equipo.',$e instanceof InvalidReceptionDateException=>'La recepcion no puede registrarse antes de la entrega.',$e instanceof InvalidScannerStatusException=>'La transicion de estado solicitada no esta permitida.',default=>'No fue posible completar la operacion.'};}
}
