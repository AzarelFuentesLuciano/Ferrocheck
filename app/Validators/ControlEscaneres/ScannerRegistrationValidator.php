<?php
declare(strict_types=1);
namespace App\Validators\ControlEscaneres;

use App\Domain\ControlEscaneres\{ScannerCode,ScannerStatus};
use App\DTO\ControlEscaneres\ScannerCreateData;
use App\Exceptions\ControlEscaneres\ScannerRegistrationValidationException;

final class ScannerRegistrationValidator
{
    private const LENGTHS=['tag'=>40,'brand'=>100,'model'=>120,'serial'=>120,'iccid'=>32,'network'=>80,'plan'=>80,'activity'=>255,'location'=>255,'age'=>120,'observations'=>500];

    public function validate(array $input,ScannerCode $generatedCode,?array $area,?array $organizationalArea=null):ScannerCreateData
    {
        $values=$this->values($input);$errors=[];
        foreach(self::LENGTHS as$field=>$max)if(mb_strlen($values[$field])>$max)$errors[$field]='No debe exceder '.$max.' caracteres.';
        if($values['tag']==='')$errors['tag']='El TAG es obligatorio.';
        if($values['brand']==='')$errors['brand']='La marca es obligatoria.';
        if($values['model']==='')$errors['model']='El modelo es obligatorio.';
        if(!$this->isValidImei($values['imei']))$errors['imei']='Captura un IMEI válido de 15 dígitos.';
        if($values['phone']!==''&&!$this->digits($values['phone'],10,15))$errors['phone']='Captura un teléfono válido de 10 a 15 dígitos.';
        if($values['area_id']===''||$area===null||!(bool)($area['activo']??false))$errors['area_id']='Selecciona un área activa del catálogo.';
        if(!in_array($values['status'],ScannerStatus::VALUES,true))$errors['status']='Selecciona un estado permitido.';
        if(!in_array($values['active'],['0','1'],true))$errors['active']='Selecciona una condición de actividad válida.';
        if($values['organizational_area_id']!==''&&($organizationalArea===null||!(bool)($organizationalArea['activo']??false)))$errors['organizational_area_id']='Selecciona un área organizacional activa.';
        if($values['active']==='1'&&$values['organizational_area_id']==='')$errors['organizational_area_id']='El área organizacional propietaria es obligatoria para un escáner activo.';
        try{$code=$values['code']===''?$generatedCode:new ScannerCode($values['code']);}catch(\Throwable){$errors['code']='Usa el formato SC-0001.';$code=$generatedCode;}
        if($errors!==[])throw new ScannerRegistrationValidationException($errors,$values);
        return new ScannerCreateData($code,'scanner:'.$code->value,$values['brand'],$values['model'],new ScannerStatus($values['status']),$this->nullable($values['serial']),$this->nullable($values['imei']),$this->nullable($values['iccid']),(int)$values['area_id'],$values['tag'],$this->nullable($values['phone']),$this->nullable($values['network']),$this->nullable($values['plan']),$this->nullable($values['activity']),(string)$area['nombre'],$this->nullable($values['location']),$this->nullable($values['age']),$this->nullable($values['observations']),null,$values['active']==='1',$values['organizational_area_id']===''?null:(int)$values['organizational_area_id']);
    }

    public function values(array $input):array
    {
        $fields=['code','tag','brand','model','serial','imei','phone','iccid','network','plan','activity','area_id','organizational_area_id','location','age','status','active','observations'];$out=[];
        foreach($fields as$field)$out[$field]=preg_replace('/\s+/u',' ',trim((string)($input[$field]??'')))??'';
        $out['code']=strtoupper($out['code']);$out['tag']=strtoupper($out['tag']);
        $out['imei']=$this->normalizeImei((string)($input['imei']??''));
        $out['phone']=$this->numeric($out['phone']);$out['iccid']=$this->numeric($out['iccid']);
        return$out;
    }

    public function normalizeImei(string $value):string{return trim($value);}
    public function isValidImei(string $value):bool{$value=$this->normalizeImei($value);return$value===''||(bool)preg_match('/^[0-9]{15}$/D',$value);}
    private function numeric(string$value):string{return preg_replace('/[\s()+.\-]/','',$value)??$value;}
    private function digits(string$value,int$min,int$max):bool{return(bool)preg_match('/^\d{'.$min.','.$max.'}$/',$value);}
    private function nullable(string$value):?string{return$value===''?null:$value;}
}
