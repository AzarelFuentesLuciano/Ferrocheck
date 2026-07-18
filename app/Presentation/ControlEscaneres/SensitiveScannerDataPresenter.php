<?php
declare(strict_types=1);namespace App\Presentation\ControlEscaneres;
final class SensitiveScannerDataPresenter{public function imei(?string$v):string{return$this->mask($v,4,11);}public function phone(?string$v):string{return$this->mask($v,4,6);}public function iccid(?string$v):string{return$this->mask($v,4,8);}private function mask(?string$v,int$visible,int$minimum):string{if($v===null||$v==='')return'—';$tail=mb_substr($v,-$visible);return str_repeat('*',max($minimum,mb_strlen($v)-$visible)).$tail;}}
