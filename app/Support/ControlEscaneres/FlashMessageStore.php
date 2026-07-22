<?php
declare(strict_types=1);
namespace App\Support\ControlEscaneres;
final class FlashMessageStore
{
    private const KEY='_ce_flash';
    private const FORM_KEY='_ce_form';
    public function __construct(private array &$session) {}
    public function add(string$type,string$message):void{if(!in_array($type,['success','warning','error'],true))throw new \InvalidArgumentException('Tipo de mensaje invalido.');$this->session[self::KEY][]=compact('type','message');}
    public function consume():array{$items=$this->session[self::KEY]??[];unset($this->session[self::KEY]);return is_array($items)?$items:[];}
    public function keepForm(string$form,array$values,array$errors=[]):void{$this->session[self::FORM_KEY][$form]=['values'=>$values,'errors'=>$errors];}
    public function consumeForm(string$form):array{$data=$this->session[self::FORM_KEY][$form]??[];unset($this->session[self::FORM_KEY][$form]);return is_array($data)?$data:[];}
    public function setReturnUrl(string$url):void{$this->session['_ce_return_url']=$url;}
}
