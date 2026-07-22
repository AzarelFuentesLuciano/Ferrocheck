<?php
declare(strict_types=1);
namespace App\Factories;
use App\Core\Database;
use App\Repositories\ControlEscaneres\Pdo\{PdoAuditQueryRepository,PdoAuditRepository,PdoEvidenceRepository,PdoInspectionDifferenceRepository,PdoScannerAreaRepository,PdoScannerCatalogQuery,PdoScannerDashboardQuery,PdoScannerHistoryQuery,PdoScannerIncidentRepository,PdoScannerInspectionRepository,PdoScannerMaintenanceRepository,PdoScannerMovementRepository,PdoScannerRepository};
use App\Repositories\ControlEscaneres\TransactionManager;
use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;
use App\Services\ControlEscaneres\Entrega\ScannerDeliveryService;
use App\Services\ControlEscaneres\Incidencias\ScannerIncidentService;
use App\Services\ControlEscaneres\Mantenimiento\ScannerMaintenanceService;
use App\Services\ControlEscaneres\Recepcion\ScannerReceptionService;
use App\Services\ControlEscaneres\Shared\{InspectionComparisonService,ScannerStateMachine,SystemBusinessClock,UuidOperationalFolioGenerator};
use App\Services\ControlEscaneres\Qr\ScannerQrCodeService;
use App\Services\ControlEscaneres\Evidence\EvidenceFileStorage;
use App\Services\ControlEscaneres\Documents\MovementReceiptPdfService;
use App\Services\ControlEscaneres\Import\ScannerInventoryImporter;
use App\Services\ControlEscaneres\Reports\ScannerReportService;
use App\Services\ControlEscaneres\Catalogo\ScannerRegistrationService;
use App\Services\ControlEscaneres\Validaciones\{IncidentPolicy,MaintenancePolicy,MovementPolicy,ScannerAvailabilityPolicy};
final class ControlEscaneresServiceFactory
{
    public function __construct(private ?\PDO$pdo=null){}
    private function pdo():\PDO{return$this->pdo??=Database::getConnection();}
    public function scanners():PdoScannerRepository{return new PdoScannerRepository($this->pdo());}
    public function movements():PdoScannerMovementRepository{return new PdoScannerMovementRepository($this->pdo());}
    public function inspections():PdoScannerInspectionRepository{return new PdoScannerInspectionRepository($this->pdo());}
    public function incidents():PdoScannerIncidentRepository{return new PdoScannerIncidentRepository($this->pdo());}
    public function evidence():PdoEvidenceRepository{return new PdoEvidenceRepository($this->pdo());}
    public function history():PdoScannerHistoryQuery{return new PdoScannerHistoryQuery($this->pdo());}
    public function auditQuery():PdoAuditQueryRepository{return new PdoAuditQueryRepository($this->pdo());}
    public function catalog():PdoScannerCatalogQuery{return new PdoScannerCatalogQuery($this->pdo());}
    public function dashboard():PdoScannerDashboardQuery{return new PdoScannerDashboardQuery($this->pdo());}
    public function areas():PdoScannerAreaRepository{return new PdoScannerAreaRepository($this->pdo());}
    public function maintenanceRecords():PdoScannerMaintenanceRepository{return new PdoScannerMaintenanceRepository($this->pdo());}
    public function businessClock():SystemBusinessClock{return$this->clock();}
    public function transactions():TransactionManager{return new TransactionManager($this->pdo());}
    public function qr():ScannerQrCodeService{return new ScannerQrCodeService($this->pdo());}
    public function evidenceStorage():EvidenceFileStorage{return new EvidenceFileStorage(dirname(__DIR__,2).'/storage/evidencias/control-escaneres');}
    public function movementPdf():MovementReceiptPdfService{return new MovementReceiptPdfService($this->pdo(),$this->evidenceStorage());}
    public function inventoryImporter():ScannerInventoryImporter{return new ScannerInventoryImporter($this->pdo());}
    public function reports():ScannerReportService{return new ScannerReportService($this->pdo());}
    public function registration():ScannerRegistrationService{return new ScannerRegistrationService($this->scanners(),$this->evidence(),$this->evidenceStorage(),new TransactionManager($this->pdo()),$this->audit());}
    private function clock():SystemBusinessClock{return new SystemBusinessClock();}
    private function audit():ScannerAuditService{return new ScannerAuditService(new PdoAuditRepository($this->pdo()),$this->clock());}
    public function auditService():ScannerAuditService{return$this->audit();}
    public function delivery():ScannerDeliveryService{$a=new ScannerAvailabilityPolicy();$m=new MovementPolicy();return new ScannerDeliveryService($this->scanners(),$this->movements(),$this->inspections(),$this->evidence(),new TransactionManager($this->pdo()),new ScannerStateMachine(),new UuidOperationalFolioGenerator($this->clock()),$this->audit(),$a,$m);}
    public function reception():ScannerReceptionService{$a=new ScannerAvailabilityPolicy();$m=new MovementPolicy();return new ScannerReceptionService($this->scanners(),$this->movements(),$this->inspections(),$this->evidence(),new TransactionManager($this->pdo()),new ScannerStateMachine(),new InspectionComparisonService(),$this->clock(),$this->audit(),$a,$m,new PdoInspectionDifferenceRepository($this->pdo()),$this->incidents());}
    public function incident():ScannerIncidentService{return new ScannerIncidentService($this->scanners(),$this->incidents(),$this->evidence(),new TransactionManager($this->pdo()),new ScannerStateMachine(),$this->clock(),$this->audit(),new ScannerAvailabilityPolicy(),new IncidentPolicy());}
    public function maintenance():ScannerMaintenanceService{return new ScannerMaintenanceService($this->scanners(),$this->movements(),$this->evidence(),new TransactionManager($this->pdo()),new ScannerStateMachine(),$this->audit(),new ScannerAvailabilityPolicy(),new MovementPolicy(),new MaintenancePolicy(),new PdoScannerMaintenanceRepository($this->pdo()));}
}
