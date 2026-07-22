<?php
declare(strict_types=1);
namespace App\Repositories\ControlEscaneres\Pdo;
final class PdoScannerAreaRepository extends AbstractPdoRepository
{
    public function listWithMetrics():array{return$this->stmt("SELECT a.id,a.nombre,a.activo,COUNT(DISTINCT s.id) equipos,COUNT(DISTINCT CASE WHEN i.estado NOT IN('resuelta','cancelada') THEN i.id END) incidencias_abiertas,COUNT(DISTINCT CASE WHEN m.estado='abierto' THEN m.id END) pendientes FROM scanner_areas a LEFT JOIN scanners s ON s.area_id=a.id OR s.area_habitual=a.nombre LEFT JOIN scanner_incidencias i ON i.scanner_id=s.id LEFT JOIN scanner_movimientos m ON m.scanner_id=s.id GROUP BY a.id,a.nombre,a.activo ORDER BY a.nombre")->fetchAll(\PDO::FETCH_ASSOC);}
    public function findActiveById(int$id):?array{$row=$this->stmt('SELECT id,nombre,activo FROM scanner_areas WHERE id=:id AND activo=1',['id'=>$id])->fetch(\PDO::FETCH_ASSOC);return is_array($row)?$row:null;}
    public function create(string$name):int{$this->stmt('INSERT INTO scanner_areas(nombre,activo) VALUES(:name,1)',['name'=>$this->name($name)]);return(int)$this->pdo->lastInsertId();}
    public function rename(int$id,string$name):void{$this->stmt('UPDATE scanner_areas SET nombre=:name WHERE id=:id',['id'=>$id,'name'=>$this->name($name)]);}
    public function deactivate(int$id):void{$this->stmt('UPDATE scanner_areas SET activo=0 WHERE id=:id',['id'=>$id]);}
    public function reactivate(int$id):void{$this->stmt('UPDATE scanner_areas SET activo=1 WHERE id=:id',['id'=>$id]);}
    private function name(string$value):string{$value=trim($value);if($value===''||mb_strlen($value)>120)throw new\InvalidArgumentException('Nombre de área inválido.');return$value;}
}
