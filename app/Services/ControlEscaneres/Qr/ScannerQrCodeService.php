<?php
declare(strict_types=1);
namespace App\Services\ControlEscaneres\Qr;
use Endroid\QrCode\{ErrorCorrectionLevel,QrCode,RoundBlockSizeMode};use Endroid\QrCode\Encoding\Encoding;use Endroid\QrCode\Writer\PngWriter;
final class ScannerQrCodeService{public function __construct(private \PDO$pdo){}public function png(int$id,int$size=300):array{$s=$this->pdo->prepare('SELECT codigo,codigo_qr,tag_original FROM scanners WHERE id=:id');$s->execute(['id'=>$id]);$r=$s->fetch(\PDO::FETCH_ASSOC);if(!is_array($r))throw new\OutOfBoundsException('Escáner no encontrado.');$q=new QrCode(data:$r['codigo_qr'],encoding:new Encoding('UTF-8'),errorCorrectionLevel:ErrorCorrectionLevel::High,size:max(160,min(1000,$size)),margin:12,roundBlockSizeMode:RoundBlockSizeMode::Margin);$o=(new PngWriter())->write($q);return['bytes'=>$o->getString(),'mime'=>$o->getMimeType(),'code'=>$r['codigo'],'tag'=>$r['tag_original']];}}
