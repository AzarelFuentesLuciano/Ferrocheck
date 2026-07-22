<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Documents;

use App\Repositories\ControlEscaneres\Pdo\PdoEvidenceRepository;
use App\Services\ControlEscaneres\Evidence\EvidenceFileStorage;
use App\Services\ControlEscaneres\Qr\ScannerQrCodeService;
use Dompdf\{Dompdf, Options};

final class MovementReceiptPdfService
{
    public function __construct(private \PDO $pdo, private ?EvidenceFileStorage $storage = null) {}

    public function render(int $id): array
    {
        $movement = $this->one('SELECT m.*,s.codigo,s.tag_original,s.marca,s.modelo,s.numero_serie,s.red,s.area_habitual,s.ubicacion,s.estado estado_scanner FROM scanner_movimientos m JOIN scanners s ON s.id=m.scanner_id WHERE m.id=:id', $id);
        if ($movement === null) throw new \OutOfBoundsException('Movimiento no encontrado.');
        $inspections = $this->all('SELECT * FROM scanner_inspecciones WHERE movimiento_id=:id ORDER BY inspeccionada_at', $id);
        $details = $this->all('SELECT i.tipo,d.componente,d.estado FROM scanner_inspeccion_detalles d JOIN scanner_inspecciones i ON i.id=d.inspeccion_id WHERE i.movimiento_id=:id ORDER BY i.tipo,d.componente', $id);
        $differences = $this->all('SELECT componente,valor_anterior,valor_nuevo,clasificacion,requiere_revision FROM scanner_inspeccion_diferencias WHERE movimiento_id=:id ORDER BY componente', $id);
        $incidents = $this->all('SELECT tipo,severidad,descripcion,estado,resolucion FROM scanner_incidencias WHERE movimiento_id=:id ORDER BY reportada_at', $id);
        $evidences = (new PdoEvidenceRepository($this->pdo))->listByMovementId($id);
        $storage = $this->storage ?? new EvidenceFileStorage(dirname(__DIR__,4) . '/storage/evidencias/control-escaneres');
        $qr = (new ScannerQrCodeService($this->pdo))->png((int) $movement['scanner_id'], 220);
        $qrUri = 'data:' . $qr['mime'] . ';base64,' . base64_encode($qr['bytes']);
        $h = static fn(mixed $value): string => htmlspecialchars((string) ($value ?? '—'), ENT_QUOTES, 'UTF-8');

        $detailRows = '';
        foreach ($details as $row) $detailRows .= '<tr><td>' . $h($row['tipo']) . '</td><td>' . $h($row['componente']) . '</td><td>' . $h($row['estado']) . '</td></tr>';
        $differenceRows = '';
        foreach ($differences as $row) $differenceRows .= '<tr><td>' . $h($row['componente']) . '</td><td>' . $h($row['valor_anterior']) . '</td><td>' . $h($row['valor_nuevo']) . '</td><td>' . $h(str_replace('_', ' ', $row['clasificacion'])) . ((int) $row['requiere_revision'] ? ' · Revisión' : '') . '</td></tr>';
        $incidentRows = '';
        foreach ($incidents as $row) $incidentRows .= '<tr><td>' . $h($row['tipo']) . '</td><td>' . $h($row['severidad']) . '</td><td>' . $h($row['estado']) . '</td><td>' . $h($row['resolucion'] ?? $row['descripcion']) . '</td></tr>';
        $inspectionSummary = '';
        foreach ($inspections as $inspection) $inspectionSummary .= '<p><b>' . $h(ucfirst($inspection['tipo'])) . ':</b> batería ' . $h($inspection['bateria_porcentaje']) . '% · valoración ' . ($inspection['calificacion'] === null ? '—' : $h(round((int) $inspection['calificacion'] / 20, 1)) . '/5 (' . $h(round((int) $inspection['calificacion'] / 10, 1)) . '/10)') . ' · ' . $h($inspection['inspeccionada_at']) . '</p>';

        $photos = '';
        $signatures = '';
        foreach ($evidences as $evidence) {
            try { $file = $storage->read($evidence); } catch (\Throwable) { continue; }
            $uri = 'data:' . $file['mime'] . ';base64,' . base64_encode($file['bytes']);
            $figure = '<figure><img src="' . $uri . '"><figcaption>' . $h(str_replace('_', ' ', $evidence->type)) . '<br>' . $h($evidence->capturedAt->format('Y-m-d H:i:s')) . '</figcaption></figure>';
            str_starts_with($evidence->type, 'firma_') ? $signatures .= $figure : $photos .= $figure;
        }

        $duration = $movement['duracion_segundos'] === null ? 'Pendiente' : $this->duration((int) $movement['duracion_segundos']);
        $html = '<!doctype html><html lang="es"><meta charset="utf-8"><style>'
            . '@page{margin:22mm 14mm 18mm}body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#172033}header{border-bottom:3px solid #1769aa;margin-bottom:14px;padding-bottom:8px}h1{margin:0;color:#123f6d;font-size:22px}h2{color:#123f6d;font-size:14px;margin-top:16px}table{width:100%;border-collapse:collapse;margin:7px 0}td,th{border:1px solid #bcc7d3;padding:5px;text-align:left}th{background:#eaf2f8}.identity{display:table;width:100%}.identity>div{display:table-cell;vertical-align:top}.qr{width:150px;text-align:center}.qr img{width:125px}.grid{display:grid;grid-template-columns:1fr 1fr}.evidence{font-size:0}.evidence figure{display:inline-block;width:30%;margin:1%;vertical-align:top;font-size:8px;text-align:center;page-break-inside:avoid}.evidence img{max-width:100%;height:105px;object-fit:contain}.signatures figure{width:46%}.signatures img{height:85px}footer{position:fixed;bottom:-12mm;left:0;right:0;border-top:1px solid #bcc7d3;padding-top:4px;color:#5b6773}</style>'
            . '<header><h1>VASCOR OPS</h1><b>FORMATO DE ENTREGA Y RECEPCIÓN DE ESCÁNER</b></header>'
            . '<div class="identity"><div><p><b>Folio:</b> ' . $h($movement['folio']) . '</p><p><b>Equipo:</b> ' . $h($movement['codigo']) . ' · TAG ' . $h($movement['tag_original']) . '</p><p><b>Marca / modelo / serie:</b> ' . $h($movement['marca']) . ' / ' . $h($movement['modelo']) . ' / ' . $h($movement['numero_serie']) . '</p><p><b>Operador / No. empleado:</b> ' . $h($movement['persona_entrega_nombre']) . ' / ' . $h($movement['numero_empleado']) . '</p><p><b>Área:</b> ' . $h($movement['area_nombre'] ?? $movement['area_habitual']) . ' · <b>Turno:</b> ' . $h($movement['turno']) . '</p><p><b>Supervisor:</b> ' . $h($movement['supervisor_nombre']) . ' · <b>Entregó:</b> ' . $h($movement['responsable_entrega_nombre']) . '</p></div><div class="qr"><img src="' . $qrUri . '"><br>' . $h($movement['codigo']) . '</div></div>'
            . '<h2>Entrega y recepción</h2><p><b>Entrega:</b> ' . $h($movement['entregado_at']) . ' · <b>Recepción:</b> ' . $h($movement['recibido_at'] ?? 'Pendiente') . '</p><p><b>Devolvió:</b> ' . $h($movement['devolucion_recibida_por_nombre']) . ' · <b>Recibió:</b> ' . $h($movement['responsable_recepcion_nombre']) . ' · <b>Duración:</b> ' . $h($duration) . '</p>' . $inspectionSummary
            . '<h2>Checklist</h2><table><thead><tr><th>Inspección</th><th>Componente</th><th>Estado</th></tr></thead><tbody>' . $detailRows . '</tbody></table>'
            . '<h2>Diferencias</h2><table><thead><tr><th>Componente</th><th>Entrega</th><th>Recepción</th><th>Clasificación</th></tr></thead><tbody>' . ($differenceRows ?: '<tr><td colspan="4">Sin diferencias registradas.</td></tr>') . '</tbody></table>'
            . '<h2>Incidencias</h2><table><thead><tr><th>Tipo</th><th>Severidad</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>' . ($incidentRows ?: '<tr><td colspan="4">Sin incidencias asociadas.</td></tr>') . '</tbody></table>'
            . '<h2>Fotografías</h2><div class="evidence">' . ($photos ?: '<p>Sin fotografías.</p>') . '</div><h2>Firmas</h2><div class="evidence signatures">' . ($signatures ?: '<p>Sin firmas.</p>') . '</div>'
            . '<p><b>Estado final:</b> ' . $h($movement['estado_scanner']) . '</p><p><b>Observaciones:</b> ' . $h($movement['observaciones']) . '</p><footer>Generado ' . date('Y-m-d H:i:s') . ' · VASCOR OPS · Documento reproducible sin alterar el historial.</footer></html>';

        $options = new Options(); $options->set('isRemoteEnabled', false); $options->set('isHtml5ParserEnabled', true);
        $pdf = new Dompdf($options); $pdf->loadHtml($html, 'UTF-8'); $pdf->setPaper('A4'); $pdf->render();
        return ['bytes' => $pdf->output(), 'filename' => 'movimiento-' . $movement['folio'] . '.pdf'];
    }

    private function one(string $query, int $id): ?array { $statement=$this->pdo->prepare($query);$statement->execute(['id'=>$id]);$row=$statement->fetch(\PDO::FETCH_ASSOC);return is_array($row)?$row:null; }
    private function all(string $query, int $id): array { $statement=$this->pdo->prepare($query);$statement->execute(['id'=>$id]);return $statement->fetchAll(\PDO::FETCH_ASSOC); }
    private function duration(int $seconds): string { $hours=intdiv($seconds,3600);$minutes=intdiv($seconds%3600,60);return $hours.' h '.$minutes.' min'; }
}
