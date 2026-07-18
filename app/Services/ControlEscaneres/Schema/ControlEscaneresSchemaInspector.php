<?php
declare(strict_types=1);namespace App\Services\ControlEscaneres\Schema;use App\DTO\ControlEscaneres\{SchemaCheck,SchemaInspectionResult};
final class ControlEscaneresSchemaInspector
{
    public const TABLES=['scanners','scanner_movimientos','scanner_inspecciones','scanner_inspeccion_detalles','scanner_incidencias','scanner_evidencias','auditoria_eventos'];
    private const CRITICAL=['scanners'=>['codigo','codigo_qr','estado','activo'],'scanner_movimientos'=>['scanner_id','folio','estado','entregado_at'],'scanner_incidencias'=>['scanner_id','movimiento_id','severidad','estado']];
    public function inspect(\PDO$pdo):SchemaInspectionResult
    {
        $driver=(string)$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);$tables=$this->tables($pdo,$driver);$present=array_values(array_intersect(self::TABLES,$tables));$missing=array_values(array_diff(self::TABLES,$present));$checks=[new SchemaCheck('driver',in_array($driver,['mysql','sqlite'],true)?'PASS':'BLOCKED','Driver PDO '.$driver)];
        if(!in_array('scanners',$tables,true)){$checks[]=new SchemaCheck('schema','WARN','El esquema canónico está ausente.');return new SchemaInspectionResult('absent',$checks,self::TABLES,[],[]);}
        $scannerColumns=$this->columns($pdo,$driver,'scanners');if(!in_array('codigo_qr',$scannerColumns,true)){$checks[]=new SchemaCheck('legacy','BLOCKED','La tabla scanners es incompatible con el esquema canónico.');return new SchemaInspectionResult('legacy_incompatible',$checks,$missing,['scanners.codigo_qr'],[]);}
        $missingColumns=[];foreach(self::CRITICAL as$table=>$columns)if(in_array($table,$tables,true)){foreach(array_diff($columns,$this->columns($pdo,$driver,$table))as$column)$missingColumns[]=$table.'.'.$column;}
        if($missing||$missingColumns){$checks[]=new SchemaCheck('schema','BLOCKED','El esquema canónico está parcial.');return new SchemaInspectionResult('canonical_partial',$checks,$missing,$missingColumns,[]);}
        $recommended=[];$indexes=$this->indexes($pdo,$driver,'scanners');if(!in_array('idx_ce_scanners_estado_activo',$indexes,true))$recommended[]='idx_ce_scanners_estado_activo';$checks[]=new SchemaCheck('schema','PASS','Las tablas y columnas críticas están presentes.');$checks[]=new SchemaCheck('indices',$recommended?'WARN':'PASS',$recommended?'Faltan índices recomendados.':'Índice principal verificado.');$checks[]=new SchemaCheck('migration_registry','WARN','No existe un registro versionado de migraciones; las tablas no prueban ejecución.');return new SchemaInspectionResult('canonical_complete',$checks,[],[],$recommended);
    }
    private function tables(\PDO$p,string$d):array{if($d==='sqlite')return array_column($p->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_ASSOC),'name');$s=$p->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()');return array_column($s->fetchAll(\PDO::FETCH_ASSOC),'table_name');}
    private function columns(\PDO$p,string$d,string$t):array{if($d==='sqlite')return array_column($p->query('PRAGMA table_info('.$t.')')->fetchAll(\PDO::FETCH_ASSOC),'name');$s=$p->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:table');$s->execute(['table'=>$t]);return array_column($s->fetchAll(\PDO::FETCH_ASSOC),'column_name');}
    private function indexes(\PDO$p,string$d,string$t):array{if($d==='sqlite')return array_column($p->query('PRAGMA index_list('.$t.')')->fetchAll(\PDO::FETCH_ASSOC),'name');$s=$p->prepare('SELECT DISTINCT index_name FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=:table');$s->execute(['table'=>$t]);return array_column($s->fetchAll(\PDO::FETCH_ASSOC),'index_name');}
}
