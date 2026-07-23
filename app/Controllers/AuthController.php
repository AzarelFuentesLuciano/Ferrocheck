<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Auth\Csrf;use App\Services\{AuthService,GeneralAuditService};
final class AuthController
{
    public function __construct(private AuthService$auth,private Csrf$csrf,private GeneralAuditService$audit,private array&$session){}
    public function dispatch(string$method,array$query,array$post,array$server):void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');
        $action=(string)($query['accion']??'login');
        if($action==='logout'){$this->logout($method,$post,$server);return;}
        if($method==='POST'){$this->login($post,$server);return;}
        $current=$this->auth->current();
        if($current!==null){header('Location: '.$this->auth->safeReturn(isset($query['return'])?(string)$query['return']:null),true,302);return;}
        $csrfToken=$this->csrf->token();$message=$this->consume();$returnUrl=$this->auth->safeReturn(isset($query['return'])?(string)$query['return']:null);require dirname(__DIR__).'/Views/auth/login.php';
    }
    private function login(array$post,array$server):void
    {
        $return=isset($post['return'])?(string)$post['return']:null;
        if(!$this->csrf->validate((string)($post['_csrf']??''))){$this->flash('La sesión del formulario expiró.');$this->redirectLogin($return);return;}
        try{$url=$this->auth->login((string)($post['usuario']??''),(string)($post['password']??''),$return,$server);$id=(int)($this->session['user_id']??0);$this->audit->record($id,'auth.login','usuario',$id,'exito',[],[],[],$this->ip($server));$this->csrf->rotate();header('Location: '.$url,true,303);}
        catch(\Throwable$e){$this->audit->record(null,'auth.login','usuario',null,'rechazado',[],[],['usuario_hash'=>hash('sha256',mb_strtolower(trim((string)($post['usuario']??''))))],$this->ip($server));$this->flash($e instanceof \DomainException?$e->getMessage():'No fue posible iniciar sesión.');$this->redirectLogin($return);}
    }
    private function logout(string$method,array$post,array$server):void
    {
        if($method!=='POST'){http_response_code(405);header('Allow: POST');echo'Método no permitido.';return;}
        if(!$this->csrf->validate((string)($post['_csrf']??''))){http_response_code(403);echo'Solicitud inválida.';return;}
        $id=(int)($this->session['user_id']??0);$this->auth->logout();$this->audit->record($id?:null,'auth.logout','usuario',$id?:null,'exito',[],[],[],$this->ip($server));header('Clear-Site-Data: "cache"');header('Location: '.BASE_URL.'/index.php?modulo=auth',true,303);
    }
    private function redirectLogin(?string$return=null):void{$safe=$this->auth->safeReturn($return);header('Location: '.BASE_URL.'/index.php?modulo=auth&return='.rawurlencode($safe),true,303);}
    private function flash(string$message):void{$this->session['_auth_flash']=$message;}
    private function consume():?string{$m=$this->session['_auth_flash']??null;unset($this->session['_auth_flash']);return is_string($m)?$m:null;}
    private function ip(array$s):?string{$ip=$s['REMOTE_ADDR']??null;return is_string($ip)&&filter_var($ip,FILTER_VALIDATE_IP)?$ip:null;}
}
