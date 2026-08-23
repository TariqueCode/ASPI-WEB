<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 2) . '/config.php';

header('Content-Type: application/json; charset=utf-8');
function uploadJson(array $data,int $status=200): never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_FILES['file'])) uploadJson(['success'=>false,'message'=>'No file'],422);
$f=$_FILES['file'];
if(($f['error']??99)!==UPLOAD_ERR_OK) uploadJson(['success'=>false,'message'=>'Upload failed'],422);
if(($f['size']??0)>30*1024*1024) uploadJson(['success'=>false,'message'=>'File exceeds 30MB limit'],422);
$mime=function_exists('mime_content_type')?(mime_content_type($f['tmp_name'])?:''):'';
$map=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','application/pdf'=>'pdf','video/mp4'=>'mp4','video/webm'=>'webm','video/ogg'=>'ogg'];
if(!isset($map[$mime])) uploadJson(['success'=>false,'message'=>'Only JPEG, PNG, GIF, WEBP, PDF and video files are allowed'],422);
$dir=dirname(__DIR__,2).'/assets/uploads/';if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))uploadJson(['success'=>false,'message'=>'Upload directory unavailable'],500);
$name=bin2hex(random_bytes(12)).'.'.$map[$mime];
if(!move_uploaded_file($f['tmp_name'],$dir.$name))uploadJson(['success'=>false,'message'=>'Upload failed'],500);
uploadJson(['success'=>true,'url'=>'assets/uploads/'.$name]);
