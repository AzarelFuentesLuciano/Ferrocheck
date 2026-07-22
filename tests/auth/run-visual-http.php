<?php
declare(strict_types=1);
require dirname(__DIR__).'/control-escaneres/bootstrap.php';
require_once dirname(__DIR__,2).'/config/config.php';
$user=(string)getenv('AUTH_VISUAL_USER');$password=(string)getenv('AUTH_VISUAL_PASSWORD');
if($user===''||$password==='')throw new RuntimeException('Faltan credenciales efímeras.');
final class VisualClient{public array$c=[];public function get(string$url,string$method='GET',array$data=[]):array{$h=[];if($this->c)$h[]='Cookie: '.implode('; ',array_map(fn($k,$v)=>$k.'='.$v,array_keys($this->c),$this->c));$o=['method'=>$method,'ignore_errors'=>true,'follow_location'=>0,'header'=>implode("\r\n",$h)];if($method==='POST'){$o['header'].="\r\nContent-Type: application/x-www-form-urlencoded";$o['content']=http_build_query($data);}$body=file_get_contents($url,false,stream_context_create(['http'=>$o]));$rh=$http_response_header??[];foreach($rh as$x)if(preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i',$x,$m))$this->c[$m[1]]=$m[2];preg_match('/\s(\d{3})\s/',$rh[0]??'',$m);$location='';foreach($rh as$x)if(str_starts_with(strtolower($x),'location:'))$location=trim(substr($x,9));return[(int)($m[1]??0),(string)$body,$location];}}
function visualToken(string$html):string{preg_match('/name="_csrf"\s+value="([^"]+)"/',$html,$m);return html_entity_decode($m[1]??'',ENT_QUOTES,'UTF-8');}
$base='http://127.0.0.1/Ferrocheck/public';$client=new VisualClient();
[$loginStatus,$login]=$client->get($base.'/index.php?modulo=auth');
test('login carga CSS, JS y texto accesible',fn()=>ok($loginStatus===200&&str_contains($login,'assets/css/auth.css')&&str_contains($login,'assets/js/auth.js')&&str_contains($login,'Bienvenido a VASCOR OPS')&&!preg_match('/Registrarse|Crear cuenta/i',$login)));
[$cssStatus,$css]=$client->get($base.'/assets/css/auth.css');[$jsStatus,$js]=$client->get($base.'/assets/js/auth.js');
test('animación publicada respeta movimiento reducido y ciclo',fn()=>ok($cssStatus===200&&$jsStatus===200&&str_contains($css,'prefers-reduced-motion')&&str_contains($js,'erasing')));
[$postStatus,$ignored,$location]=$client->get($base.'/index.php?modulo=auth','POST',['_csrf'=>visualToken($login),'usuario'=>$user,'password'=>$password]);
test('login efímero redirige',fn()=>ok($postStatus===303&&$location!==''));
foreach(['usuarios','roles']as$section){[$status,$html]=$client->get($base.'/index.php?modulo=administracion&seccion='.$section);$dom=new DOMDocument();@$dom->loadHTML($html);$xpath=new DOMXPath($dom);test("$section usa shell y estructura responsive",fn()=>ok($status===200&&$xpath->query('//header[contains(@class,"app-header")]')->length===1&&$xpath->query('//aside[contains(@class,"app-sidebar")]')->length===1&&$xpath->query('//div[contains(@class,"table-responsive")]/table')->length===1&&$xpath->query('//form[@method="post"]//button[contains(.,"Cerrar sesión")]')->length===1&&str_contains($html,'assets/css/admin.css')));}
[$filterStatus,$filterHtml]=$client->get($base.'/index.php?modulo=administracion&seccion=usuarios&q=azarel&activo=');test('filtro HTTP azarel con estado Todos responde seguro',fn()=>ok($filterStatus===200&&str_contains(mb_strtolower($filterHtml),'azarel')&&!str_contains($filterHtml,'SQLSTATE')&&!str_contains($filterHtml,'Stack trace')));
[$invalidStatus,$invalidHtml]=$client->get($base.'/index.php?modulo=administracion&seccion=usuarios&q=azarel&activo=invalido');test('estado HTTP inválido se normaliza a Todos',fn()=>ok($invalidStatus===200&&str_contains(mb_strtolower($invalidHtml),'azarel')));
[$adminCssStatus,$adminCss]=$client->get($base.'/assets/css/admin.css');test('CSS administrativo se publica y limita desbordamiento',fn()=>ok($adminCssStatus===200&&str_contains($adminCss,'overflow-x:auto')&&str_contains($adminCss,'@media(max-width:720px)')));
finish('Authentication Visual HTTP');
