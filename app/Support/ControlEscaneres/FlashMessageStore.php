<?php
declare(strict_types=1);
namespace App\Support\ControlEscaneres;
final class FlashMessageStore
{
    private const KEY='_ce_flash';
    public function __construct(private array &$session) {}
    public function add(string$type,string$message):void{if(!in_array($type,['success','warning','error'],true))throw new \InvalidArgumentException('Tipo de mensaje invalido.');$this->session[self::KEY][]=compact('type','message');}
    public function consume():array{$items=$this->session[self::KEY]??[];unset($this->session[self::KEY]);return is_array($items)?$items:[];}
}
