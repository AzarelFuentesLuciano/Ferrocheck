<?php
declare(strict_types=1);
namespace App\Services\ControlEscaneres\Shared;
use App\DTO\ControlEscaneres\InspectionDifference;
final class InspectionComparisonService implements InspectionComparisonServiceInterface
{
    private const RANK=['no funciona'=>0,'faltante'=>0,'dañado'=>1,'danado'=>1,'regular'=>2,'bueno'=>3,'excelente'=>4,'no aplica'=>null];
    public function compare(array $delivery,array $reception):array{$initial=[];foreach($delivery as$detail)$initial[$detail['componente']]=$detail;$out=[];foreach($reception as$detail){$before=$initial[$detail['componente']]??null;[$result,$classification,$review]=$this->classify($before,$detail);$out[]=new InspectionDifference($detail['componente'],$before,$detail,$result,$classification,$review);}return$out;}
    private function classify(?array$before,array$after):array{if($before===null)return['sin_referencia','sin_cambio',false];$old=mb_strtolower((string)($before['estado']??''));$new=mb_strtolower((string)($after['estado']??''));if($old===$new)return['igual','sin_cambio',false];if($new==='faltante')return['empeoro','faltante',true];if($new==='no funciona')return['empeoro','daño_critico',true];$a=self::RANK[$old]??null;$b=self::RANK[$new]??null;if($a===null||$b===null)return['no_comparable','sin_cambio',false];if($b>$a)return['mejoro','mejora',false];$drop=$a-$b;return['empeoro',$drop>=2?'deterioro_importante':'deterioro_menor',$drop>=2];}
}
