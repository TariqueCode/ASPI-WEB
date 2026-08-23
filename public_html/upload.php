<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
header('Content-Type: application/json; charset=utf-8');
function uploadOut(array $v,int $s=200):never{http_response_code($s);echo json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if(empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in']!==true) uploadOut(['error'=>'Unauthorized'],401);
if($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_FILES['file'])) uploadOut(['error'=>'No file uploaded'],422);
$f=$_FILES['file'];if(($f['error']??99)!==UPLOAD_ERR_OK)uploadOut(['error'=>'The file upload failed.'],422);
if(($f['size']??0)>30*1024*1024)uploadOut(['error'=>'File size cannot exceed 30MB.'],422);
$mime=function_exists('mime_content_type')?(mime_content_type($f['tmp_name'])?:''):'';
$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','application/pdf'=>'pdf','video/mp4'=>'mp4','video/webm'=>'webm','video/ogg'=>'ogg'];
$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
if(!isset($allowed[$mime]) || !in_array($ext,['jpg','jpeg','png','gif','webp','pdf','mp4','webm','ogg'],true))uploadOut(['error'=>'শুধু JPEG, PNG, GIF, WEBP, PDF এবং ভিডিও অনুমোদিত।'],422);
$dir=__DIR__.'/assets/uploads/';if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))uploadOut(['error'=>'Upload directory unavailable'],500);
$name=bin2hex(random_bytes(12)).'_'.time().'.'.$allowed[$mime];
if(!move_uploaded_file($f['tmp_name'],$dir.$name))uploadOut(['error'=>'আপলোড ব্যর্থ।'],500);
uploadOut(['url'=>'assets/uploads/'.$name,'type'=>'media']);
