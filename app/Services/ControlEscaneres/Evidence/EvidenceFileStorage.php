<?php
declare(strict_types=1);
namespace App\Services\ControlEscaneres\Evidence;
use App\DTO\ControlEscaneres\{AuthenticatedActorData,ScannerEvidenceMetadata};
final class EvidenceFileStorage
{
    private const MIME=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    public function __construct(private string$root){}
    public function upload(array$file,int$scannerId,string$type,AuthenticatedActorData$actor):ScannerEvidenceMetadata
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($file['tmp_name']??'')))throw new \InvalidArgumentException('Archivo de evidencia inválido.');
        $size=(int)($file['size']??0);if($size<1||$size>5*1024*1024)throw new \InvalidArgumentException('Cada imagen debe pesar entre 1 byte y 5 MB.');
        return$this->persist((string)$file['tmp_name'],$size,$scannerId,$type,$actor,true);
    }
    public function signature(string$dataUrl,int$scannerId,string$type,AuthenticatedActorData$actor):ScannerEvidenceMetadata
    {
        if(!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#',$dataUrl,$m))throw new \InvalidArgumentException('Firma digital inválida.');$bytes=base64_decode($m[1],true);if($bytes===false||strlen($bytes)<100||strlen($bytes)>1024*1024)throw new \InvalidArgumentException('Firma vacía o demasiado grande.');$tmp=tempnam(sys_get_temp_dir(),'ce-sign-');if($tmp===false)throw new \RuntimeException('No fue posible preparar la firma.');file_put_contents($tmp,$bytes);try{return$this->persist($tmp,strlen($bytes),$scannerId,$type,$actor,false);}finally{@unlink($tmp);}
    }
    public function remove(ScannerEvidenceMetadata$evidence):void{$path=$this->absolute($evidence->storagePath);if(is_file($path))@unlink($path);}
    public function read(ScannerEvidenceMetadata$evidence):array{$path=$this->absolute($evidence->storagePath);if(!is_file($path)||filesize($path)!==$evidence->sizeBytes||!hash_equals(strtolower($evidence->sha256),strtolower((string)hash_file('sha256',$path))))throw new \RuntimeException('La evidencia no está disponible o no superó la verificación de integridad.');$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);if(!is_string($mime)||$mime!==$evidence->mimeType||!isset(self::MIME[$mime]))throw new \RuntimeException('El tipo real de la evidencia no coincide.');$bytes=file_get_contents($path);if($bytes===false)throw new \RuntimeException('No fue posible leer la evidencia.');return['bytes'=>$bytes,'mime'=>$mime,'size'=>strlen($bytes)];}
    private function persist(string$source,int$size,int$scannerId,string$type,AuthenticatedActorData$actor,bool$uploaded):ScannerEvidenceMetadata
    {
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($source);if(!is_string($mime)||!isset(self::MIME[$mime]))throw new \InvalidArgumentException('Formato no permitido; usa JPEG, PNG o WebP.');if(@getimagesize($source)===false)throw new \InvalidArgumentException('El archivo no es una imagen válida.');$dir=date('Y/m');$folder=$this->root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$dir);if(!is_dir($folder)&&!mkdir($folder,0750,true)&&!is_dir($folder))throw new \RuntimeException('No fue posible crear el almacenamiento de evidencias.');$name=bin2hex(random_bytes(20)).'.'.self::MIME[$mime];$target=$folder.DIRECTORY_SEPARATOR.$name;$ok=$uploaded?move_uploaded_file($source,$target):copy($source,$target);if(!$ok)throw new \RuntimeException('No fue posible guardar la evidencia.');$relative=$dir.'/'.$name;return new ScannerEvidenceMetadata($scannerId,$type,$relative,$mime,$size,hash_file('sha256',$target),new \DateTimeImmutable(),$actor);
    }
    private function absolute(string$relative):string{$relative=str_replace('\\','/',$relative);if(str_contains($relative,'..')||str_starts_with($relative,'/'))throw new \InvalidArgumentException('Ruta de evidencia inválida.');return$this->root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);}
}
