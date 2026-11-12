<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$local = in_array($ip, ['127.0.0.1','::1'], true);
$file = __DIR__ . '/contenido.json';
function reply(int $code,array $payload):never {
 http_response_code($code);
 echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
 exit;
}
function readContent(string $file):array {
 if(!is_file($file)) return [];
 $data=json_decode((string)file_get_contents($file),true);
 return is_array($data) && is_array($data['content']??null) ? $data['content'] : [];
}
// Keep only simple inline formatting. No scripts, links, event handlers or attributes.
function cleanHtml(string $html):string {
 $html=strip_tags($html,'<b><strong><i><em><u><s><br><span><code><sup><sub>');
 $dom=new DOMDocument('1.0','UTF-8');
 $old=libxml_use_internal_errors(true);
 $dom->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
 libxml_clear_errors();libxml_use_internal_errors($old);
 $root=$dom->getElementById('root');
 if(!$root) return htmlspecialchars(strip_tags($html),ENT_QUOTES,'UTF-8');
 $walk=function(DOMNode $node) use (&$walk):void {
  foreach(iterator_to_array($node->childNodes) as $child) {
   if($child instanceof DOMElement){
    foreach(iterator_to_array($child->attributes) as $attribute) $child->removeAttribute($attribute->name);
    $walk($child);
   }
  }
 };
 $walk($root);
 $result='';foreach($root->childNodes as $child)$result.=$dom->saveHTML($child);
 return $result;
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='GET') reply(200,['ok'=>true,'canEdit'=>$local,'content'=>readContent($file)]);
if($method!=='POST') {header('Allow: GET, POST');reply(405,['ok'=>false,'error'=>'Método no permitido']);}
if(!$local) reply(403,['ok'=>false,'error'=>'Edición disponible solo desde localhost']);
$raw=file_get_contents('php://input',false,null,0,1024*1024+1);
if($raw===false || strlen($raw)>1024*1024) reply(413,['ok'=>false,'error'=>'Contenido demasiado grande']);
$input=json_decode($raw,true);
if(!is_array($input) || !is_array($input['content']??null)) reply(400,['ok'=>false,'error'=>'JSON inválido']);
$original=readContent($file);
if(!$original) reply(500,['ok'=>false,'error'=>'Falta el JSON inicial']);
$clean=$original;
foreach($input['content'] as $key=>$value) {
 if(!is_string($key) || !array_key_exists($key,$original) || !is_string($value)) reply(400,['ok'=>false,'error'=>'Clave o valor no válido']);
 if(strlen($value)>50000) reply(413,['ok'=>false,'error'=>'Texto demasiado largo']);
 $clean[$key]=cleanHtml($value);
}
$payload=json_encode(['version'=>1,'content'=>$clean],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_INVALID_UTF8_SUBSTITUTE);
$tmp=tempnam(__DIR__,'.contenido-');
if($tmp===false || file_put_contents($tmp,$payload,LOCK_EX)===false) reply(500,['ok'=>false,'error'=>'No se pudo escribir el archivo']);
if(!rename($tmp,$file)){@unlink($tmp);reply(500,['ok'=>false,'error'=>'No se pudo reemplazar el JSON']);}
reply(200,['ok'=>true]);
