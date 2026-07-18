<?php
declare(strict_types=1); namespace App\Validators\ControlEscaneres;
final class AuditPayloadSanitizer {private const SENSITIVE=['password','token','cookie','pin','puk','secret','authorization'];public static function sanitize(?array $data):?array{if($data===null)return null;$out=[];foreach($data as $k=>$v){if(in_array(strtolower((string)$k),self::SENSITIVE,true))continue;$out[$k]=is_array($v)?self::sanitize($v):$v;}return $out;}}
