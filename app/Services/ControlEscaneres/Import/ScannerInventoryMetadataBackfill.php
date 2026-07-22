<?php
declare(strict_types=1);
namespace App\Services\ControlEscaneres\Import;

final class ScannerInventoryMetadataBackfill
{
    public function __construct(private \PDO$pdo){}
    public function apply(array$preview):array
    {
        if(($preview['stats']['conflictos']??-1)!==3||($preview['stats']['invalidos']??-1)!==0)throw new \RuntimeException('La vista previa no coincide con la validación autorizada.');
        $allowed=[];foreach($preview['items']as$item)if(in_array($item['result'],['existente','actualizable'],true))$allowed[$item['code']]=true;
        $areaInsert=$this->pdo->prepare('INSERT IGNORE INTO scanner_areas(nombre,activo) VALUES(:name,1)');$areaFind=$this->pdo->prepare('SELECT id FROM scanner_areas WHERE nombre=:name');
        $update=$this->pdo->prepare("UPDATE scanners SET tag_original=COALESCE(NULLIF(:tag,''),tag_original),red=COALESCE(NULLIF(:network,''),red),plan=COALESCE(NULLIF(:plan,''),plan),actividad_habitual=COALESCE(NULLIF(:activity,''),actividad_habitual),area_habitual=COALESCE(NULLIF(:area,''),area_habitual),area_id=COALESCE(:area_id,area_id),ubicacion=COALESCE(NULLIF(:location,''),ubicacion),antiguedad_descriptiva=COALESCE(NULLIF(:age,''),antiguedad_descriptiva),observaciones=COALESCE(NULLIF(:observations,''),observaciones) WHERE codigo=:code");
        $updated=0;$this->pdo->beginTransaction();try{foreach($preview['records']as$r){if(!isset($allowed[$r['code']]))continue;$areaId=null;if($r['area']!==null){$areaInsert->execute(['name'=>$r['area']]);$areaFind->execute(['name'=>$r['area']]);$areaId=(int)$areaFind->fetchColumn();}$update->execute(['tag'=>$r['tag'],'network'=>$r['network'],'plan'=>$r['plan'],'activity'=>$r['activity'],'area'=>$r['area'],'area_id'=>$areaId?:null,'location'=>$r['location'],'age'=>$r['age'],'observations'=>$r['observations'],'code'=>$r['code']]);$updated+=$update->rowCount()>0?1:0;}
            $audit=$this->pdo->prepare("INSERT INTO auditoria_eventos(usuario_id,accion,modulo,entidad,resultado,valor_nuevo_json,metadata_json,created_at)VALUES(NULL,'completar_metadata_importada','control-escaneres','scanner_import','exito',:values,:metadata,CURRENT_TIMESTAMP(6))");$audit->execute(['values'=>json_encode(['updated'=>$updated],JSON_THROW_ON_ERROR),'metadata'=>json_encode(['conflicts_excluded'=>3,'empty_values_overwritten'=>false,'pin_puk_imported'=>false],JSON_THROW_ON_ERROR)]);$this->pdo->commit();}catch(\Throwable$e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw$e;}return['updated'=>$updated,'areas'=>(int)$this->pdo->query('SELECT COUNT(*) FROM scanner_areas')->fetchColumn()];
    }
}
