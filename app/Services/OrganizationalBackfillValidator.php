<?php
declare(strict_types=1);
namespace App\Services;
final class OrganizationalBackfillValidator
{
    public function __construct(private \PDO$pdo){}
    public function report():array
    {
        $scalar=fn(string$sql):int=>(int)$this->pdo->query($sql)->fetchColumn();
        $rows=fn(string$sql):array=>$this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return[
            'generated_at'=>(new \DateTimeImmutable())->format(DATE_ATOM),
            'read_only'=>true,
            'usuarios'=>[
                'activos_y_area_principal'=>$rows('SELECT u.nombre,u.usuario,a.nombre area_principal FROM usuarios u LEFT JOIN usuario_areas ua ON ua.usuario_id=u.id AND ua.activo=1 AND ua.es_principal=1 LEFT JOIN areas_organizacionales a ON a.id=ua.area_id WHERE u.activo=1 ORDER BY u.nombre'),
                'activos_sin_area'=>$scalar('SELECT COUNT(*) FROM usuarios u WHERE u.activo=1 AND NOT EXISTS(SELECT 1 FROM usuario_areas ua JOIN areas_organizacionales a ON a.id=ua.area_id AND a.activo=1 WHERE ua.usuario_id=u.id AND ua.activo=1)'),
                'activos_multiples_principales'=>$scalar('SELECT COUNT(*) FROM(SELECT ua.usuario_id FROM usuario_areas ua JOIN usuarios u ON u.id=ua.usuario_id AND u.activo=1 WHERE ua.activo=1 AND ua.es_principal=1 GROUP BY ua.usuario_id HAVING COUNT(*)>1)x'),
                'asignaciones_areas_inactivas'=>$scalar('SELECT COUNT(*) FROM usuario_areas ua JOIN areas_organizacionales a ON a.id=ua.area_id WHERE ua.activo=1 AND a.activo=0'),
                'relaciones_duplicadas'=>$scalar('SELECT COUNT(*) FROM(SELECT usuario_id,area_id FROM usuario_areas GROUP BY usuario_id,area_id HAVING COUNT(*)>1)x'),
                'inactivos_con_relaciones_activas_advertencia'=>$scalar('SELECT COUNT(DISTINCT u.id) FROM usuarios u JOIN usuario_areas ua ON ua.usuario_id=u.id AND ua.activo=1 WHERE u.activo=0'),
            ],
            'escaneres'=>[
                'total'=>$scalar('SELECT COUNT(*) FROM scanners'),'sin_area'=>$scalar('SELECT COUNT(*) FROM scanners WHERE area_organizacional_id IS NULL'),
                'area_inexistente'=>$scalar('SELECT COUNT(*) FROM scanners s LEFT JOIN areas_organizacionales a ON a.id=s.area_organizacional_id WHERE s.area_organizacional_id IS NOT NULL AND a.id IS NULL'),
                'area_inactiva'=>$scalar('SELECT COUNT(*) FROM scanners s JOIN areas_organizacionales a ON a.id=s.area_organizacional_id WHERE a.activo=0'),
                'totales_por_area'=>$rows("SELECT COALESCE(a.nombre,'Sin asignar') area,COUNT(*) total FROM scanners s LEFT JOIN areas_organizacionales a ON a.id=s.area_organizacional_id GROUP BY a.id,a.nombre ORDER BY area"),
            ],
            'modulos'=>[
                'areas_sin_modulos'=>$rows('SELECT a.id,a.nombre FROM areas_organizacionales a WHERE a.activo=1 AND NOT EXISTS(SELECT 1 FROM area_modulos am JOIN modulos m ON m.id=am.modulo_id AND m.activo=1 WHERE am.area_id=a.id AND am.activo=1)'),
                'modulos_sin_areas'=>$rows('SELECT m.id,m.nombre FROM modulos m WHERE m.activo=1 AND NOT EXISTS(SELECT 1 FROM area_modulos am JOIN areas_organizacionales a ON a.id=am.area_id AND a.activo=1 WHERE am.modulo_id=m.id AND am.activo=1)'),
                'excepciones_contradictorias'=>$rows('SELECT usuario_id,modulo_id,COUNT(*) total FROM usuario_modulos WHERE activo=1 GROUP BY usuario_id,modulo_id HAVING COUNT(DISTINCT tipo)>1'),
                'inactivos_asociados'=>$scalar('SELECT COUNT(*) FROM area_modulos am JOIN modulos m ON m.id=am.modulo_id WHERE am.activo=1 AND m.activo=0'),
            ],
        ];
    }
}
