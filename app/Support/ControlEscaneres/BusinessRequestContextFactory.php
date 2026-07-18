<?php
declare(strict_types=1);
namespace App\Support\ControlEscaneres;
use App\DTO\ControlEscaneres\BusinessRequestContext;
final class BusinessRequestContextFactory
{
    public function __construct(private array $server, private string $sessionId) {}
    public function create(string $source='control-escaneres-web'): BusinessRequestContext
    {
        $ip=isset($this->server['REMOTE_ADDR'])&&filter_var($this->server['REMOTE_ADDR'],FILTER_VALIDATE_IP)?$this->server['REMOTE_ADDR']:null;
        $correlation=$this->cleanHeader($this->server['HTTP_X_CORRELATION_ID']??null);
        return new BusinessRequestContext(bin2hex(random_bytes(16)),$ip,hash('sha256',$this->sessionId),$source,$correlation);
    }
    private function cleanHeader(mixed $value): ?string { if(!is_string($value)||!preg_match('/^[A-Za-z0-9._:-]{1,100}$/',$value))return null;return$value; }
}
