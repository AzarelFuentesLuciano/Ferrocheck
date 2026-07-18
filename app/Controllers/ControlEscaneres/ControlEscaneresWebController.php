<?php
declare(strict_types=1);
namespace App\Controllers\ControlEscaneres;
use App\Domain\ControlEscaneres\{BatteryPercentage,IncidentSeverity,ScannerFolio,ScannerStatus};
use App\DTO\ControlEscaneres\{IncidentResolutionData,IncidentSeverityChangeData,MaintenanceCommandData,ScannerIncidentCreateData,ScannerInspectionDetailData,ScannerMovementCreateData,ScannerReceptionData};
use App\Factories\ControlEscaneresServiceFactory;
use App\Presentation\ControlEscaneres\SensitiveScannerDataPresenter;
use App\Presentation\ControlEscaneres\ScannerDashboardViewModelFactory;
use App\Security\ControlEscaneres\{AuthenticatedActorProviderInterface,CsrfTokenManagerInterface};
use App\Services\ControlEscaneres\Shared\ScannerStateMachine;
use App\Support\ControlEscaneres\{BusinessRequestContextFactory,ControlEscaneresErrorMapper,FlashMessageStore};
use App\ViewModels\ControlEscaneres\{ScannerDeliveryFormViewModel,ScannerIncidentFormViewModel,ScannerMaintenanceFormViewModel,ScannerReceptionFormViewModel};
final class ControlEscaneresWebController
{
    private const COMPONENTS=['bateria','pantalla','touch','botones','lector','wifi','datos_moviles','accesorios'];
    public function __construct(private ControlEscaneresServiceFactory$factory,private AuthenticatedActorProviderInterface$actors,private CsrfTokenManagerInterface$csrf,private BusinessRequestContextFactory$contexts,private FlashMessageStore$flash,private ControlEscaneresErrorMapper$errors){}
    public function dispatch(array$query,array$post,string$method):void{$section=trim((string)($query['seccion']??'dashboard'));if($method==='POST'){$this->post($section,$post);return;}$this->get($section,$query);}
    private function get(string$section,array$query):void
    {
        try{$messages=$this->flash->consume();$id=max(0,(int)($query['scanner_id']??0));
            if($section==='dashboard'){$dashboardViewModel=(new ScannerDashboardController($this->factory->dashboard(),$this->factory->businessClock(),new ScannerDashboardViewModelFactory()))->index(isset($query['rango'])?(string)$query['rango']:null,defined('BASE_URL')?BASE_URL:'');}
            elseif($section==='catalogo'){$catalogViewModel=(new ScannerCatalogController($this->factory->catalog(),new SensitiveScannerDataPresenter()))->index($query,$messages);}
            elseif($section==='entrega'){$s=$id?$this->factory->scanners()->findById($id):null;$deliveryForm=new ScannerDeliveryFormViewModel($s?->id,$s?->code->value,$s?->status->value,$this->csrf->token(),self::COMPONENTS,$messages);}
            elseif($section==='recepcion'){$s=$id?$this->factory->scanners()->findById($id):null;$m=$s?$this->factory->movements()->findOpenByScannerId($s->id):null;$receptionForm=new ScannerReceptionFormViewModel($s?->id,$s?->code->value,$m?->id,$m?->personaEntregaNombre,$m?->entregadoAt->format('Y-m-d H:i:s'),$this->csrf->token(),self::COMPONENTS,$messages);}
            elseif($section==='incidencias'){$s=$id?$this->factory->scanners()->findById($id):null;$m=$s?$this->factory->movements()->findOpenByScannerId($s->id):null;$list=$s?$this->factory->incidents()->listByScannerId($s->id):[];$allowed=$s?array_map(fn($x)=>$x->value,(new ScannerStateMachine())->allowedTransitionsFrom($s->status)):[];$incidentForm=new ScannerIncidentFormViewModel($s?->id,$s?->code->value,$m?->id,$list,$allowed,$this->csrf->token(),$messages);}
            elseif($section==='mantenimiento'){$s=$id?$this->factory->scanners()->findById($id):null;$allowed=$s?array_map(fn($x)=>$x->value,(new ScannerStateMachine())->allowedTransitionsFrom($s->status)):[];$maintenanceForm=new ScannerMaintenanceFormViewModel($s?->id,$s?->code->value,$s?->status->value,$allowed,$this->csrf->token(),$messages);}
            elseif($section==='expediente'&&$id>0){$historyViewModel=(new ScannerHistoryController($this->factory->history(),$this->factory->auditQuery(),new SensitiveScannerDataPresenter()))->show($id,$messages);}
        }catch(\Throwable$e){error_log('ControlEscaneres GET: '.get_class($e));$integrationError=$this->errors->message($e);}
        require dirname(__DIR__,2).'/Views/inventario/importar.php';
    }
    private function post(string$section,array$post):void
    {
        try{if(!$this->csrf->validate((string)($post['_csrf']??'')))throw new\DomainException('CSRF');$actor=$this->actors->getActor();$context=$this->contexts->create();$id=$this->positive($post,'scanner_id');
            if($section==='entrega'){$result=$this->factory->delivery()->deliver(new ScannerMovementCreateData($id,new ScannerFolio('MOV-20000101-TEMP01'),$this->required($post,'person_name'),$this->required($post,'employee_number'),$this->required($post,'shift'),new\DateTimeImmutable(),$actor,$this->battery($post),$this->rating($post),$this->optional($post,'observations'),$this->details($post)),$actor,$context);$this->flash->add('success','Entrega registrada. Folio: '.$result->movement->folio->value);}
            elseif($section==='recepcion'){$result=$this->factory->reception()->receive(new ScannerReceptionData($this->positive($post,'movement_id'),$id,$this->required($post,'receiver_name'),$this->battery($post),$this->rating($post),$this->optional($post,'observations'),$this->details($post)),$actor,$context);$this->flash->add('success','Recepcion registrada. Estado: '.$result->resultingStatus->value);}
            elseif($section==='incidencias'){$this->incident($post,$id,$actor,$context);$this->flash->add('success','Incidencia actualizada correctamente.');}
            elseif($section==='mantenimiento'){$action=$this->required($post,'operation');$target=$action==='return'?$this->validatedTransition($id,(string)($post['resulting_status']??'')):null;$this->factory->maintenance()->execute(new MaintenanceCommandData($id,$action,$this->required($post,'reason'),$this->optional($post,'observations'),resultingStatus:$target),$actor,$context);$this->flash->add('success','Mantenimiento actualizado correctamente.');}
            else throw new\DomainException('Operacion');$this->csrf->rotate();
        }catch(\Throwable$e){error_log('ControlEscaneres POST: '.get_class($e));$this->flash->add('error',$e->getMessage()==='CSRF'?'La sesion del formulario expiro. Intente nuevamente.':$this->errors->message($e));}
        $url=(defined('BASE_URL')?BASE_URL:'').'/index.php?modulo=control-escaneres&seccion='.rawurlencode($section).((int)($post['scanner_id']??0)>0?'&scanner_id='.(int)$post['scanner_id']:'');header('Location: '.$url,true,303);
    }
    private function incident(array$p,int$id,$actor,$context):void{$action=(string)($p['operation']??'report');if($action==='resolve'){$this->factory->incident()->resolve(new IncidentResolutionData($this->positive($p,'incident_id'),$this->required($p,'resolution'),$this->validatedTransition($id,(string)($p['resulting_status']??''))),$actor,$context);}elseif($action==='severity'){$incident=$this->factory->incidents()->findById($this->positive($p,'incident_id'))??throw new\DomainException('Incidencia');$this->factory->incident()->changeSeverity(new IncidentSeverityChangeData($incident->id,$incident->severity,new IncidentSeverity($this->required($p,'severity')),$this->required($p,'reason')),$actor,$context);}else{$m=(int)($p['movement_id']??0);$this->factory->incident()->report(new ScannerIncidentCreateData($id,$this->required($p,'type'),new IncidentSeverity($this->required($p,'severity')),$this->required($p,'description'),new\DateTimeImmutable(),$actor,$m>0?$m:null),$actor,$context);}}
    private function required(array$p,string$k):string{$v=trim((string)($p[$k]??''));if($v===''||mb_strlen($v)>500)throw new\InvalidArgumentException('Dato requerido invalido.');return$v;}
    private function optional(array$p,string$k):?string{$v=trim((string)($p[$k]??''));return$v===''?null:$v;}
    private function positive(array$p,string$k):int{$v=filter_var($p[$k]??null,FILTER_VALIDATE_INT);if($v===false||$v<1)throw new\InvalidArgumentException('Identificador invalido.');return$v;}
    private function battery(array$p):?BatteryPercentage{$v=$p['battery']??'';return$v===''?null:new BatteryPercentage((int)$v);}
    private function rating(array$p):?int{$v=$p['rating']??'';return$v===''?null:(int)$v;}
    private function details(array$p):array{$out=[];foreach(self::COMPONENTS as$c){$v=trim((string)($p['component'][$c]??''));if($v!=='')$out[]=new ScannerInspectionDetailData($c,$v);}return$out;}
    private function validatedTransition(int$id,string$target):ScannerStatus{$s=$this->factory->scanners()->findById($id)??throw new\DomainException('Scanner');$t=new ScannerStatus($target);(new ScannerStateMachine())->assertTransition($s->status,$t);return$t;}
}
