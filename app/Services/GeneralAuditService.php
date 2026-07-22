<?php
declare(strict_types=1);
namespace App\Services;

use App\Validators\ControlEscaneres\AuditPayloadSanitizer;

final class GeneralAuditService
{
    public function __construct(private \PDO $pdo) {}
    public function record(?int $actorId,string $action,string $entity,?int $entityId,string $result,array $before=[],array $after=[],array $metadata=[],?string $ip=null):void
    {
        $json=static fn(array$data):?string=>$data===[]?null:json_encode(AuditPayloadSanitizer::sanitize($data),JSON_THROW_ON_ERROR);
        $stmt=$this->pdo->prepare('INSERT INTO auditoria_eventos(usuario_id,accion,modulo,entidad,entidad_id,resultado,valor_anterior_json,valor_nuevo_json,metadata_json,ip,request_id,session_fingerprint)VALUES(:actor,:action,\'autenticacion\',:entity,:entity_id,:result,:before,:after,:metadata,:ip,:request,NULL)');
        $stmt->execute(['actor'=>$actorId,'action'=>$action,'entity'=>$entity,'entity_id'=>$entityId,'result'=>$result,'before'=>$json($before),'after'=>$json($after),'metadata'=>$json($metadata),'ip'=>$ip,'request'=>bin2hex(random_bytes(16))]);
    }
}
