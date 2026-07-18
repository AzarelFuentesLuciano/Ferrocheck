<?php
declare(strict_types=1);
namespace App\Security\ControlEscaneres;
use App\DTO\ControlEscaneres\AuthenticatedActorData;
interface AuthenticatedActorProviderInterface { public function getActor(): AuthenticatedActorData; }
