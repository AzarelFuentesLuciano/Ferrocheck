<?php
declare(strict_types=1);
namespace App\Services\ControlEscaneres\Catalogo;
use App\Domain\ControlEscaneres\Scanner;use App\DTO\ControlEscaneres\{AuthenticatedActorData,BusinessRequestContext,ScannerCreateData};use App\Repositories\ControlEscaneres\Contracts\{EvidenceRepositoryInterface,TransactionManagerInterface};use App\Repositories\ControlEscaneres\Pdo\PdoScannerRepository;use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;use App\Services\ControlEscaneres\Evidence\EvidenceFileStorage;
final class ScannerRegistrationService
{
    public function __construct(private PdoScannerRepository$scanners,private EvidenceRepositoryInterface$evidence,private EvidenceFileStorage$storage,private TransactionManagerInterface$transactions,private ScannerAuditService$audit){}
    public function register(ScannerCreateData$data,?array$photo,AuthenticatedActorData$actor,BusinessRequestContext$context):Scanner
    {
        $stored=null;try{return$this->transactions->transactional(function()use($data,$photo,$actor,$context,&$stored){$this->scanners->assertIdentityAvailable(0,$data->tag,$data->serial,$data->imei,$data->iccid);$scanner=$this->scanners->create($data,$actor->userId);$this->scanners->setOrganizationalArea($scanner->id,$data->organizationalAreaId,$actor->userId);if(is_array($photo)&&($photo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$stored=$this->storage->upload($photo,$scanner->id,'fotografia_principal',$actor);$this->evidence->create($stored);$this->scanners->setMainPhoto($scanner->id,$stored->storagePath,$actor->userId);}$this->audit->record('scanner.create','scanner',$scanner->id,[],['codigo'=>$data->code->value,'tag'=>$data->tag,'estado'=>$data->status->value,'activo'=>$data->active,'area_organizacional_id'=>$data->organizationalAreaId,'fotografia_principal'=>$stored!==null],$actor,$context);return$this->scanners->findById($scanner->id)??throw new\RuntimeException('No fue posible recuperar el escáner registrado.');});}catch(\Throwable$e){if($stored!==null)$this->storage->remove($stored);throw$e;}
    }
}
