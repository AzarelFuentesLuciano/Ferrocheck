<?php
declare(strict_types=1);
namespace App\Services\ControlEscaneres\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class ScannerInventoryExcelPreviewer
{
    private const MAP = [
        'tag'=>'tag','actividad'=>'activity','asignacion'=>'area','ubicacion'=>'location',
        'comentarios'=>'observations','red'=>'network','imei'=>'imei','chip'=>'iccid',
        'numerodechip'=>'phone','plan'=>'plan','antiguedad'=>'age',
    ];

    public function preview(string $path, array $existing=[]): array
    {
        $reader=IOFactory::createReaderForFile($path);$reader->setReadDataOnly(true);
        $sheet=$reader->load($path)->getSheetByName('INVENTARIO');
        if($sheet===null)throw new \RuntimeException('El archivo no contiene la hoja INVENTARIO.');
        $headers=[];$block='operativo';$read=0;$empty=0;$modems=0;$invalid=[];$byTag=[];
        for($row=1;$row<=$sheet->getHighestDataRow();$row++){
            $values=$sheet->rangeToArray('A'.$row.':'.$sheet->getHighestDataColumn().$row,null,true,true,false)[0];
            $first=$this->clean($values[0]??null);$normalizedFirst=$this->normalize((string)$first);
            if($normalizedFirst==='modem'){$modems++;break;}
            if($this->isHeader($values)){$headers=$this->headers($values);if($row>1)$block='danado';continue;}
            if(!$headers||!array_filter($values,fn($v)=>$this->clean($v)!==null)){$empty++;continue;}
            $read++;$record=$this->record($headers,$values,$block,$row);$tag=$record['tag'];
            if($tag===null||!preg_match('/^\d{4,}$/',$tag)){$record['result']='invalido';$record['reason']='TAG ausente o inválido';$invalid[]=$record;continue;}
            $byTag[$tag][]=$record;
        }
        $existingIndexes=$this->indexes($existing);$items=[];$allRecords=[];$candidates=[];$unique=0;$conflicts=0;$new=0;$already=0;$updatable=0;
        foreach($byTag as$tag=>$tagRecords){$unique++;$merged=$this->merge($tagRecords);if($merged['conflict']){$merged['result']='conflicto';$merged['reason']='Requiere revisión';$conflicts++;}
            else{$match=$this->existingMatch($merged,$existingIndexes);if($match===null){$merged['result']='nuevo';$new++;$candidates[]=$merged;}elseif(($match['codigo']??null)!==$merged['code']){$merged['result']='conflicto';$merged['reason']='Requiere revisión';$conflicts++;}else{$merged['existing_id']=$match['id'];$merged['result']=$this->hasNewData($merged,$match)?'actualizable':'existente';$merged['result']==='actualizable'?$updatable++:$already++;}}
            $allRecords[]=$merged;$items[]=$this->present($merged);
        }
        $seen=['imei'=>[],'iccid'=>[],'phone'=>[]];$validCandidates=[];
        foreach($candidates as$candidate){$duplicate=false;foreach(array_keys($seen)as$key){$value=$candidate[$key]??null;if($value!==null&&isset($seen[$key][$value]))$duplicate=true;}if($duplicate){$new--;$conflicts++;foreach($items as&$item)if($item['code']===$candidate['code']){$item['result']='conflicto';$item['reason']='Requiere revisión';break;}unset($item);continue;}foreach(array_keys($seen)as$key){$value=$candidate[$key]??null;if($value!==null)$seen[$key][$value]=true;}$validCandidates[]=$candidate;}
        $candidates=$validCandidates;
        foreach($invalid as$item)$items[]=$this->present($item);
        return ['stats'=>['total_leido'=>$read,'tags_unicos'=>$unique,'nuevos'=>$new,'existentes'=>$already,'actualizables'=>$updatable,'conflictos'=>$conflicts,'invalidos'=>count($invalid),'filas_vacias'=>$empty,'seccion_modem_ignorada'=>$modems>0], 'items'=>$items, 'records'=>$allRecords, 'candidates'=>$candidates];
    }

    private function isHeader(array$v):bool{return $this->normalize((string)($v[0]??''))===''&&$this->normalize((string)($v[1]??''))==='tag'||$this->normalize((string)($v[0]??''))==='#'&&$this->normalize((string)($v[1]??''))==='tag';}
    private function headers(array$v):array{$out=[];foreach($v as$i=>$h){$key=self::MAP[$this->normalize((string)$h)]??null;if($key!==null)$out[$i]=$key;}return$out;}
    private function record(array$headers,array$values,string$block,int$row):array{$r=['source_row'=>$row,'block'=>$block];foreach($headers as$i=>$key)$r[$key]=$this->clean($values[$i]??null);$r+=array_fill_keys(array_values(self::MAP),null);$r['tag']=$this->digits($r['tag']);$r['code']=$r['tag']===null?null:'SC-'.$r['tag'];foreach(['imei','iccid','phone']as$k)$r[$k]=$this->digits($r[$k]);$damage=$block==='danado'||preg_match('/(falla|dañ|roto|no funciona|touch|pantalla)/iu',(string)$r['observations']);$r['status']=$damage?'pendiente_reparacion':'disponible';$r['brand']='Por definir';$r['model']='Por definir';return$r;}
    private function merge(array$records):array
    {
        $all = $records;
        $base = array_shift($records);
        foreach($records as$r){foreach(['activity','area','location','observations','network','imei','iccid','phone','plan','age']as$k)if($base[$k]===null)$base[$k]=$r[$k];if($r['status']==='pendiente_reparacion')$base['status']=$r['status'];}
        $base['conflict']=false;
        $base['source_rows']=array_map(static fn(array$r):int=>$r['source_row'],$all);
        return$base;
    }
    private function indexes(array$rows):array{$x=['code'=>[],'imei'=>[],'iccid'=>[],'phone'=>[]];foreach($rows as$r)foreach(array_keys($x)as$k){$column=$k==='code'?'codigo':($k==='phone'?'telefono':$k);$v=$k==='code'?$this->clean($r[$column]??null):$this->digits($r[$column]??null);if($v!==null)$x[$k][$v]=$r;}return$x;}
    private function existingMatch(array$r,array$x):?array{foreach(['code','imei','iccid','phone']as$k){$v=$r[$k]??null;if($v!==null&&isset($x[$k][$v]))return$x[$k][$v];}return null;}
    private function hasNewData(array$r,array$e):bool{foreach(['imei','iccid','phone']as$k){$column=$k==='phone'?'telefono':$k;if(($e[$column]??null)===null&&$r[$k]!==null)return true;}return false;}
    private function present(array$r):array{return ['row'=>$r['source_row']??null,'code'=>$r['code']??'—','block'=>$r['block']??'—','area'=>$r['area']??'—','status'=>$r['status']??'—','imei'=>$this->mask($r['imei']??null),'iccid'=>$this->mask($r['iccid']??null),'phone'=>$this->mask($r['phone']??null),'result'=>$r['result']??'invalido','reason'=>$r['reason']??null];}
    private function clean(mixed$v):?string{if($v===null)return null;$v=trim(str_replace("\xc2\xa0",' ',(string)$v));return$v===''?null:$v;}
    private function digits(mixed$v):?string{$v=$this->clean($v);if($v===null)return null;$d=preg_replace('/\D+/','',$v);return$d===''?null:$d;}
    private function normalize(string$v):string{$v=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$v)?:$v;$v=strtolower($v);return preg_replace('/[^a-z0-9#]+/','',$v)??'';}
    private function mask(?string$v):string{if($v===null)return'—';$n=strlen($v);return$n<=4?str_repeat('•',$n):str_repeat('•',$n-4).substr($v,-4);}
}
