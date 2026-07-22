<?php
declare(strict_types=1);
namespace App\Support;

final class GlobalErrorHandler
{
    private static bool $rendered=false;

    public static function register():void
    {
        ini_set('display_errors','0');ini_set('display_startup_errors','0');ini_set('log_errors','1');
        set_exception_handler(static fn(\Throwable$error)=>self::handle($error));
        register_shutdown_function(static function():void{$error=error_get_last();if($error===null||!in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))return;self::handle(new \ErrorException($error['message'],0,$error['type'],$error['file'],$error['line']));});
    }

    public static function handle(\Throwable$error):void
    {
        if(self::$rendered)return;self::$rendered=true;$trackingId=bin2hex(random_bytes(8));
        error_log(sprintf('VASCOR OPS ERROR [%s] %s: %s at %s:%d method=%s route=%s user_id=%s%s',$trackingId,get_class($error),$error->getMessage(),$error->getFile(),$error->getLine(),(string)($_SERVER['REQUEST_METHOD']??'CLI'),(string)($_SERVER['REQUEST_URI']??''),(string)($_SESSION['user_id']??'none'),PHP_EOL.$error->getTraceAsString()));
        if(PHP_SAPI==='cli'){fwrite(STDERR,'Error interno. Seguimiento: '.$trackingId.PHP_EOL);return;}
        if(!headers_sent()){http_response_code(500);header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: no-store');}
        require dirname(__DIR__).'/Views/errors/500.php';
    }
}
