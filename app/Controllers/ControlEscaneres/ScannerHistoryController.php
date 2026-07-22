<?php
declare(strict_types=1);

namespace App\Controllers\ControlEscaneres;

use App\Presentation\ControlEscaneres\SensitiveScannerDataPresenter;
use App\Repositories\ControlEscaneres\Contracts\{AuditQueryRepositoryInterface, ScannerHistoryQueryInterface};
use App\ViewModels\ControlEscaneres\ScannerHistoryViewModel;
use App\Support\SpanishDateFormatter;

final class ScannerHistoryController
{
    public function __construct(
        private ScannerHistoryQueryInterface $history,
        private AuditQueryRepositoryInterface $audit,
        private SensitiveScannerDataPresenter $presenter,
    ) {}

    public function show(int $id, array $messages = []): ScannerHistoryViewModel
    {
        $data = $this->history->getScannerDetails($id)
            ?? throw new \DomainException('Escáner no encontrado.');

        $scanner = [
            'id' => (int) $data['id'],
            'code' => $data['codigo'],
            'qr' => $data['codigo_qr'],
            'tag' => $data['tag_original'],
            'brand' => $data['marca'],
            'model' => $data['modelo'],
            'serial' => $data['numero_serie'] ?: '—',
            'imei' => $this->presenter->imei($data['imei']),
            'phone' => $this->presenter->phone($data['telefono']),
            'iccid' => $this->presenter->iccid($data['iccid']),
            'network' => $data['red'],
            'plan' => $data['plan'],
            'activity' => $data['actividad_habitual'],
            'area' => $data['area_habitual'],
            'organizational_area' => $data['area_organizacional_nombre'] ?? null,
            'location' => $data['ubicacion'],
            'age' => $data['antiguedad_descriptiva'],
            'observations' => $data['observaciones'],
            'photo' => $data['fotografia_principal'],
            'status' => $data['estado'],
            'active' => (bool) $data['activo'],
            'conservation' => $data['indice_conservacion'],
        ];

        $movements = array_map(static fn($movement) => [
            'id' => $movement->id,
            'folio' => $movement->folio->value,
            'status' => $movement->status->value,
            'custodian' => $movement->personaEntregaNombre,
            'deliveredAt' => SpanishDateFormatter::format($movement->entregadoAt),
            'receivedAt' => SpanishDateFormatter::format($movement->recibidoAt,'Pendiente'),
        ], $this->history->listMovements($id));

        $inspections = array_map(static fn($inspection) => [
            'id' => $inspection->id,
            'type' => $inspection->type->value,
            'rating' => $inspection->rating,
            'ratingStars' => $inspection->rating === null ? null : round($inspection->rating / 20, 1),
            'ratingTen' => $inspection->rating === null ? null : round($inspection->rating / 10, 1),
            'battery' => $inspection->battery?->value,
            'at' => SpanishDateFormatter::format($inspection->inspectedAt),
        ], $this->history->listInspections($id));

        $incidents = array_map(static fn($incident) => [
            'id' => $incident->id,
            'type' => $incident->type,
            'severity' => $incident->severity->value,
            'status' => $incident->status->value,
            'at' => SpanishDateFormatter::format($incident->reportedAt),
        ], $this->history->listIncidents($id));

        $evidences = array_map(static fn($evidence) => [
            'id' => $evidence->id,
            'type' => $evidence->type,
            'mime' => $evidence->mimeType,
            'movementId' => $evidence->movementId,
            'inspectionId' => $evidence->inspectionId,
            'incidentId' => $evidence->incidentId,
            'capturedAt' => SpanishDateFormatter::format($evidence->capturedAt),
        ], $this->history->listEvidences($id));

        $audit = array_map(static fn(array $event) => [
            'action' => $event['accion'],
            'result' => $event['resultado'],
            'at' => SpanishDateFormatter::format($event['created_at']??null),
        ], $this->audit->listByScannerId($id));

        $timeline = array_map(static fn(array $event) => [
            'type' => $event['type'],
            'at' => SpanishDateFormatter::format($event['at']),
        ], $this->history->buildTimeline($id));

        return new ScannerHistoryViewModel(
            $scanner,
            $movements,
            $inspections,
            $incidents,
            $evidences,
            $audit,
            $timeline,
            $messages,
            $this->history->listDifferences($id),
            $this->history->listMaintenance($id),
            $this->history->listIncidentFollowUps($id),
        );
    }
}
