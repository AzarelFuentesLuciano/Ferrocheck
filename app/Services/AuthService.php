<?php
declare(strict_types=1);
namespace App\Services;

use App\Auth\{AuthenticatedUser,SessionSecurity};
use App\Repositories\AuthRepository;

final class AuthService
{
    public function __construct(private AuthRepository $repository, private array &$session) {}

    public function login(string $username, string $password, ?string $returnUrl, array $server): string
    {
        $username = mb_strtolower(trim($username));
        $record = $username === '' ? null : $this->repository->findForLogin($username);
        if ($record === null || !(bool)$record['activo'] || !password_verify($password, (string)$record['password_hash'])) {
            throw new \DomainException('Usuario o contraseña incorrectos.');
        }
        session_regenerate_id(true);
        $userId = (int)$record['id'];
        $hash = SessionSecurity::fingerprint(session_id());
        [$roles, $permissions] = $this->repository->rolesAndPermissions($userId);
        $this->repository->createSession($userId,$hash,$this->ip($server),$this->userAgentHash($server),new \DateTimeImmutable('+'.SessionSecurity::LIFETIME.' seconds'));
        $this->repository->touchLogin($userId);
        $this->session['user_id']=$userId;
        $this->session['auth_session_hash']=$hash;
        $this->session['auth_roles']=$roles;
        $this->session['auth_permissions']=$permissions;
        $this->session['auth_name']=(string)$record['nombre'];
        $this->session['auth_username']=(string)$record['usuario'];
        return $this->safeReturn($returnUrl);
    }

    public function current(): ?AuthenticatedUser
    {
        $id=filter_var($this->session['user_id']??null,FILTER_VALIDATE_INT);
        $hash=$this->session['auth_session_hash']??null;
        if($id===false||$id<1||!is_string($hash)||!$this->repository->validSession($id,$hash)){return null;}
        $record=$this->repository->findActiveById($id);
        if($record===null){return null;}
        [$roles,$permissions]=$this->repository->rolesAndPermissions($id);
        $this->repository->touchSession($hash);
        $this->session['auth_roles']=$roles;$this->session['auth_permissions']=$permissions;$this->session['auth_name']=(string)$record['nombre'];$this->session['auth_username']=(string)$record['usuario'];
        return new AuthenticatedUser($id,(string)$record['nombre'],(string)$record['usuario'],$roles,$permissions);
    }

    public function logout(): void
    {
        $id=(int)($this->session['user_id']??0);$hash=$this->session['auth_session_hash']??null;
        if($id>0&&is_string($hash))$this->repository->revokeSession($hash,$id,'logout');
        $this->session=[];
        if(session_status()===PHP_SESSION_ACTIVE){
            $params=session_get_cookie_params();session_destroy();
            setcookie(session_name(),'',[
                'expires'=>time()-42000,'path'=>$params['path']?:'/','domain'=>$params['domain']??'',
                'secure'=>(bool)($params['secure']??false),'httponly'=>(bool)($params['httponly']??true),
                'samesite'=>$params['samesite']??'Lax',
            ]);
        }
    }

    public function safeReturn(?string $url): string
    {
        $fallback=(defined('BASE_URL')?BASE_URL:'').'/index.php?modulo=dashboard';
        if(!is_string($url)||$url===''||preg_match('/[\r\n\x00]/',$url)||str_starts_with($url,'//'))return$fallback;
        $parts=parse_url($url);if($parts===false||isset($parts['scheme'])||isset($parts['host'])||isset($parts['user'])||isset($parts['pass']))return$fallback;
        $path=(string)($parts['path']??'');
        if(!in_array($path,['/index.php','/Ferrocheck/public/index.php'],true))return$fallback;
        return$url;
    }
    private function ip(array$server):?string{$ip=$server['REMOTE_ADDR']??null;return is_string($ip)&&filter_var($ip,FILTER_VALIDATE_IP)?$ip:null;}
    private function userAgentHash(array$server):?string{$ua=$server['HTTP_USER_AGENT']??null;return is_string($ua)&&$ua!==''?hash('sha256',$ua):null;}
}
