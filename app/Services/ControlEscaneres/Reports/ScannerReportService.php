<?php

declare(strict_types=1);

namespace App\Services\ControlEscaneres\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ScannerReportService
{
    private const TYPES = [
        'diario', 'semanal', 'mensual', 'area', 'scanner', 'responsable',
        'estado', 'incidencia', 'mantenimiento', 'pendientes', 'deterioro', 'valoracion',
    ];

    public function __construct(private \PDO $pdo)
    {
    }

    public function generate(array $input): array
    {
        $type = in_array($input['tipo'] ?? '', self::TYPES, true) ? $input['tipo'] : 'diario';
        $today = new \DateTimeImmutable('today');
        $defaultFrom = match ($type) {
            'mensual' => $today->modify('first day of this month'),
            'semanal' => $today->modify('-6 days'),
            default => $today,
        };
        $from = $this->date($input['desde'] ?? null, $defaultFrom);
        $to = $this->date($input['hasta'] ?? null, $today)->setTime(23, 59, 59);
        if ($from > $to || $to->diff($from)->days > 366) {
            throw new \InvalidArgumentException('Rango de reporte inválido.');
        }

        $fromValue = $from->format('Y-m-d H:i:s');
        $toValue = $to->format('Y-m-d H:i:s');
        $where = ['1=1'];
        $params = [
            'mov_from' => $fromValue, 'mov_to' => $toValue,
            'inc_from' => $fromValue, 'inc_to' => $toValue,
            'rating_from' => $fromValue, 'rating_to' => $toValue,
        ];

        $area = trim((string) ($input['area'] ?? ''));
        $status = trim((string) ($input['estado'] ?? ''));
        $search = trim((string) ($input['q'] ?? ''));
        if ($area !== '') {
            $where[] = 's.area_habitual = :area';
            $params['area'] = $area;
        }
        if ($status !== '') {
            $where[] = 's.estado = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $where[] = '(s.codigo LIKE :search_code OR s.tag_original LIKE :search_tag OR s.marca LIKE :search_brand OR s.modelo LIKE :search_model)';
            foreach (['search_code', 'search_tag', 'search_brand', 'search_model'] as $key) {
                $params[$key] = '%' . $search . '%';
            }
        }
        if ($type === 'pendientes') {
            $where[] = "EXISTS (SELECT 1 FROM scanner_movimientos p WHERE p.scanner_id = s.id AND p.estado IN ('abierto','vencido','con_incidencia'))";
        }
        if ($type === 'incidencia') {
            $where[] = 'EXISTS (SELECT 1 FROM scanner_incidencias x WHERE x.scanner_id = s.id AND x.reportada_at BETWEEN :filter_inc_from AND :filter_inc_to)';
            $params['filter_inc_from'] = $fromValue;
            $params['filter_inc_to'] = $toValue;
        }
        if ($type === 'mantenimiento') {
            $where[] = 'EXISTS (SELECT 1 FROM scanner_mantenimientos mt WHERE mt.scanner_id = s.id AND mt.iniciado_at BETWEEN :filter_maint_from AND :filter_maint_to)';
            $params['filter_maint_from'] = $fromValue;
            $params['filter_maint_to'] = $toValue;
        }
        if ($type === 'deterioro') {
            $where[] = "EXISTS (SELECT 1 FROM scanner_inspeccion_diferencias df JOIN scanner_movimientos dm ON dm.id = df.movimiento_id WHERE dm.scanner_id = s.id AND df.created_at BETWEEN :filter_diff_from AND :filter_diff_to AND df.clasificacion <> 'sin_cambio')";
            $params['filter_diff_from'] = $fromValue;
            $params['filter_diff_to'] = $toValue;
        }

        $sql = "SELECT s.id, s.codigo, s.tag_original, s.marca, s.modelo, s.numero_serie,
                       s.area_habitual, s.estado, s.activo, s.indice_conservacion,
                       (SELECT m.persona_entrega_nombre FROM scanner_movimientos m WHERE m.scanner_id = s.id ORDER BY m.entregado_at DESC LIMIT 1) responsable,
                       (SELECT COUNT(*) FROM scanner_movimientos m WHERE m.scanner_id = s.id AND m.entregado_at BETWEEN :mov_from AND :mov_to) movimientos,
                       (SELECT COUNT(*) FROM scanner_incidencias i WHERE i.scanner_id = s.id AND i.reportada_at BETWEEN :inc_from AND :inc_to) incidencias,
                       (SELECT ROUND(AVG(ins.calificacion) / 10, 2) FROM scanner_inspecciones ins WHERE ins.scanner_id = s.id AND ins.inspeccionada_at BETWEEN :rating_from AND :rating_to) valoracion
                FROM scanners s
                WHERE " . implode(' AND ', $where) . '
                ORDER BY s.area_habitual, s.codigo';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'type' => $type,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'rows' => $rows,
            'summary' => [
                'equipos' => count($rows),
                'movimientos' => array_sum(array_column($rows, 'movimientos')),
                'incidencias' => array_sum(array_column($rows, 'incidencias')),
                'valoracion' => $this->average(array_column($rows, 'valoracion')),
            ],
        ];
    }

    public function xlsx(array $report): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Código', 'TAG', 'Marca', 'Modelo', 'Serie', 'Área', 'Estado', 'Activo', 'Conservación', 'Responsable', 'Movimientos', 'Incidencias', 'Valoración'], null, 'A1');
        $row = 2;
        foreach ($report['rows'] as $item) {
            $sheet->fromArray([$item['codigo'], $item['tag_original'], $item['marca'], $item['modelo'], $item['numero_serie'], $item['area_habitual'], $item['estado'], (int) $item['activo'] === 1 ? 'Sí' : 'No', $item['indice_conservacion'], $item['responsable'], $item['movimientos'], $item['incidencias'], $item['valoracion']], null, 'A' . $row++);
        }
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $bytes = (string) ob_get_clean();
        $spreadsheet->disconnectWorksheets();
        return ['bytes' => $bytes, 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'filename' => $this->filename($report, 'xlsx')];
    }

    public function pdf(array $report): array
    {
        $h = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? '—'), ENT_QUOTES, 'UTF-8');
        $rows = '';
        foreach ($report['rows'] as $item) {
            $rows .= '<tr><td>' . $h($item['codigo']) . '</td><td>' . $h($item['tag_original']) . '</td><td>' . $h($item['marca'] . ' ' . $item['modelo']) . '</td><td>' . $h($item['area_habitual']) . '</td><td>' . $h($item['estado']) . '</td><td>' . $h($item['responsable']) . '</td><td>' . $h($item['incidencias']) . '</td><td>' . $h($item['valoracion']) . '</td></tr>';
        }
        $html = '<!doctype html><meta charset="utf-8"><style>@page{margin:15mm}body{font-family:DejaVu Sans;font-size:9px;color:#172033}h1{color:#123f6d}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbc6d0;padding:4px}th{background:#eaf2f8}</style><h1>VASCOR OPS · Control de Escáneres</h1><h2>Reporte ' . $h($report['type']) . '</h2><p>Periodo ' . $h($report['from']) . ' a ' . $h($report['to']) . ' · Equipos ' . $report['summary']['equipos'] . ' · Movimientos ' . $report['summary']['movimientos'] . ' · Incidencias ' . $report['summary']['incidencias'] . '</p><table><thead><tr><th>Código</th><th>TAG</th><th>Equipo</th><th>Área</th><th>Estado</th><th>Responsable</th><th>Inc.</th><th>Val.</th></tr></thead><tbody>' . $rows . '</tbody></table><p>Generado ' . date('Y-m-d H:i:s') . ' · Los identificadores sensibles no se incluyen.</p>';
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        return ['bytes' => $pdf->output(), 'mime' => 'application/pdf', 'filename' => $this->filename($report, 'pdf')];
    }

    private function date(mixed $value, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date) {
            throw new \InvalidArgumentException('Fecha inválida.');
        }
        return $date;
    }

    private function average(array $values): ?float
    {
        $values = array_values(array_filter($values, static fn (mixed $value): bool => $value !== null));
        return $values === [] ? null : round(array_sum($values) / count($values), 2);
    }

    private function filename(array $report, string $extension): string
    {
        return 'control-escaneres-' . $report['type'] . '-' . $report['from'] . '-' . $report['to'] . '.' . $extension;
    }
}
