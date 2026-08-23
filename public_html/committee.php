<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $data,int $status=200): never { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function adminOnly(): void { if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) out(['error'=>'Unauthorized'],401); }

$pdo->exec("CREATE TABLE IF NOT EXISTS organizational_committee (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 name_bn VARCHAR(150) NOT NULL DEFAULT '', name_en VARCHAR(150) NOT NULL DEFAULT '',
 designation_bn VARCHAR(150) NOT NULL DEFAULT '', designation_en VARCHAR(150) NOT NULL DEFAULT '',
 image_url VARCHAR(500) DEFAULT NULL, status TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_status_sort(status,sort_order,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$action=$_GET['action']??'';
if($action==='list'){
    $admin=!empty($_GET['admin']); if($admin)adminOnly();
    $where=$admin?'':'WHERE status=1';
    $q=$pdo->query("SELECT id,name_bn,name_en,designation_bn,designation_en,image_url,status,sort_order FROM organizational_committee {$where} ORDER BY sort_order ASC,id ASC");
    out(['committee'=>$q->fetchAll(PDO::FETCH_ASSOC)]);
}
if($action==='csrf'){adminOnly();out(['csrf_token'=>Security::generateCSRF()]);}

adminOnly();
if($action==='upload'){
    if($_SERVER['REQUEST_METHOD']!=='POST')out(['error'=>'POST required'],405);
    if(!Security::validateCSRF((string)($_POST['csrf_token']??'')))out(['error'=>'CSRF token mismatch'],419);
    $f=$_FILES['file']??null;
    if(!$f || ($f['error']??99)!==UPLOAD_ERR_OK)out(['error'=>'No valid file uploaded'],422);
    if(($f['size']??0)>5*1024*1024)out(['error'=>'Image must be 5MB or smaller'],422);
    $mime=function_exists('mime_content_type')?(mime_content_type($f['tmp_name'])?:''):'';
    $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($map[$mime]))out(['error'=>'Only JPG, PNG and WEBP images are allowed'],422);
    $dir=__DIR__.'/assets/uploads/committee/';
    if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))out(['error'=>'Upload directory unavailable'],500);
    $name=bin2hex(random_bytes(12)).'.'.$map[$mime];
    if(!move_uploaded_file($f['tmp_name'],$dir.$name))out(['error'=>'Upload failed'],500);
    out(['status'=>'success','url'=>'assets/uploads/committee/'.$name]);
}
if($_SERVER['REQUEST_METHOD']!=='POST')out(['error'=>'POST required'],405);
$body=json_decode(file_get_contents('php://input'),true)?:[];
if(!Security::validateCSRF((string)($body['csrf_token']??'')))out(['error'=>'CSRF token mismatch'],419);
if($action==='save'){
    $id=(int)($body['id']??0);$bn=trim((string)($body['name_bn']??''));$en=trim((string)($body['name_en']??''));
    if($bn===''&&$en==='')out(['error'=>'Member name is required'],422);
    $v=[$bn,$en,trim((string)($body['designation_bn']??'')),trim((string)($body['designation_en']??'')),trim((string)($body['image_url']??'')),!empty($body['status'])?1:0,(int)($body['sort_order']??0)];
    if($id){$s=$pdo->prepare('UPDATE organizational_committee SET name_bn=?,name_en=?,designation_bn=?,designation_en=?,image_url=?,status=?,sort_order=? WHERE id=?');$s->execute([...$v,$id]);}
    else{$s=$pdo->prepare('INSERT INTO organizational_committee(name_bn,name_en,designation_bn,designation_en,image_url,status,sort_order) VALUES(?,?,?,?,?,?,?)');$s->execute($v);$id=(int)$pdo->lastInsertId();}
    out(['status'=>'success','id'=>$id]);
}
if($action==='delete'){
    $id=(int)($body['id']??0);if($id<=0)out(['error'=>'Invalid member'],422);
    $s=$pdo->prepare('SELECT image_url FROM organizational_committee WHERE id=?');$s->execute([$id]);$row=$s->fetch(PDO::FETCH_ASSOC);
    $pdo->prepare('DELETE FROM organizational_committee WHERE id=?')->execute([$id]);
    if($row && !empty($row['image_url']) && str_starts_with($row['image_url'],'assets/uploads/committee/')){ $p=__DIR__.'/'.$row['image_url'];if(is_file($p))@unlink($p); }
    out(['status'=>'success']);
}
out(['error'=>'Invalid action'],404);
