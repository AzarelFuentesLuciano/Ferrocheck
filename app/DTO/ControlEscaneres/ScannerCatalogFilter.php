<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;
final readonly class ScannerCatalogFilter
{
    public const ORDER_COLUMNS=['codigo','marca','modelo','estado','activo','updated_at'];
    public function __construct(public?string$search=null,public?string$brand=null,public?string$model=null,public?string$status=null,public?bool$active=null,public?bool$withIncident=null,public int$page=1,public int$perPage=25,public string$orderBy='codigo',public string$direction='ASC'){
        foreach([$search,$brand,$model]as$v)if($v!==null&&(mb_strlen($v)>100||!preg_match('/^[\pL\pN ._\-]+$/u',$v)))throw new\InvalidArgumentException('Filtro de catalogo invalido.');
        if($status!==null&&!in_array($status,\App\Domain\ControlEscaneres\ScannerStatus::VALUES,true)||$page<1||$perPage<1||$perPage>100||!in_array($orderBy,self::ORDER_COLUMNS,true)||!in_array($direction,['ASC','DESC'],true))throw new\InvalidArgumentException('Filtro de catalogo invalido.');
    }
    public function offset():int{return($this->page-1)*$this->perPage;}
}
