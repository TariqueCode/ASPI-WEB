<?php
/**
 * API Router – Complete with all features
 * Last updated: 2026-08-16
 */

error_reporting(0);
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'session.php';
require_once 'includes/Validator.php';
require_once 'includes/RateLimiter.php';
require_once 'includes/EducationBoardResult.php';
require_once 'includes/PdfGenerator.php';

$action = $_GET['action'] ?? '';
$pdo = Database::getInstance();
$limiter = new RateLimiter($pdo);
$lang = $_GET['lang'] ?? 'bn';
$lang = in_array($lang, ['bn','en'], true) ? $lang : 'bn';
$suffix = ($lang === 'en') ? '_en' : '_bn';
$msg = static fn(string $bn, string $en): string => $lang === 'en' ? $en : $bn;

// ============================================================
// ১. ফাইল আপলোড
// ============================================================
if ($action === 'upload') {
    if (empty($_SESSION['admin_logged_in']) && !in_array($_SERVER['REQUEST_METHOD'], ['POST'], true)) {
        sendJsonResponse(['error' => 'Unauthorized'], 401);
    }
    $uploadType = $_POST['upload_type'] ?? 'media';
    $isFont = $uploadType === 'font';
    if ($isFont && empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $targetDir = $isFont ? 'assets/uploads/fonts/' : 'assets/uploads/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    if (!isset($_FILES['file'])) sendJsonResponse(['error' => $msg('কোনো ফাইল আপলোড করা হয়নি।', 'No file uploaded.')]);

    $file = $_FILES['file'];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        sendJsonResponse(['error' => $msg('ফাইল আপলোডে সমস্যা হয়েছে।', 'The file upload failed.')]);
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = function_exists('mime_content_type') ? (mime_content_type($file['tmp_name']) ?: '') : '';

    if ($isFont) {
        $allowedExt = ['ttf','woff','woff2'];
        $allowedMime = ['font/ttf','font/sfnt','font/woff','font/woff2','application/font-sfnt','application/x-font-ttf','application/octet-stream'];
        if (!in_array($ext, $allowedExt, true) || ($mime && !in_array($mime, $allowedMime, true))) {
            sendJsonResponse(['error' => $msg('শুধু TTF, WOFF এবং WOFF2 ফন্ট ফাইল অনুমোদিত।', 'Only TTF, WOFF and WOFF2 font files are allowed.')]);
        }
        if ($file['size'] > 10*1024*1024) sendJsonResponse(['error' => $msg('ফন্ট ফাইলের আকার ১০MB-এর বেশি হতে পারে না।', 'Font file size cannot exceed 10MB.')]);
    } else {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','video/mp4','video/webm','video/ogg'];
        $allowedExt = ['jpg','jpeg','png','gif','webp','pdf','mp4','webm','ogg'];
        if (!in_array($mime, $allowed, true) || !in_array($ext, $allowedExt, true)) {
            sendJsonResponse(['error' => $msg('শুধু JPEG, PNG, GIF, WEBP, PDF এবং ভিডিও অনুমোদিত।', 'Only JPEG, PNG, GIF, WEBP, PDF and video files are allowed.')]);
        }
        if ($file['size'] > 30*1024*1024) sendJsonResponse(['error' => $msg('ফাইলের আকার ৩০MB-এর বেশি হতে পারে না।', 'File size cannot exceed 30MB.')]);
    }

    $newName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $target = $targetDir . $newName;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        sendJsonResponse(['url' => $target, 'type' => $isFont ? 'font' : 'media']);
    } else {
        sendJsonResponse(['error' => $msg('আপলোড ব্যর্থ।', 'Upload failed.')]);
    }
}

// Ensure the event/news media table exists on upgraded installations.
function ensureEventMediaTable(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS event_media (
        id INT(11) NOT NULL AUTO_INCREMENT,
        event_id INT(11) NOT NULL,
        file_url VARCHAR(500) NOT NULL,
        type ENUM('image','video') NOT NULL DEFAULT 'image',
        title_bn VARCHAR(255) DEFAULT NULL,
        title_en VARCHAR(255) DEFAULT NULL,
        sort_order INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_event_media_event_id (event_id),
        KEY idx_event_media_type (type),
        KEY idx_event_media_sort (event_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Upgrade an older ASPI-final-v2 event_media schema without dropping data.
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM event_media");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[$col['Field']] = true;
    }

    if (!isset($columns['event_id'])) {
        $pdo->exec("ALTER TABLE event_media ADD COLUMN event_id INT(11) NULL AFTER id");
        if (isset($columns['content_id'])) {
            $pdo->exec("UPDATE event_media SET event_id = content_id WHERE event_id IS NULL");
        }
        $pdo->exec("ALTER TABLE event_media MODIFY event_id INT(11) NOT NULL");
    }

    if (!isset($columns['file_url'])) {
        $pdo->exec("ALTER TABLE event_media ADD COLUMN file_url VARCHAR(500) NULL");
        if (isset($columns['file_path'])) {
            $pdo->exec("UPDATE event_media SET file_url = file_path WHERE file_url IS NULL");
        }
        $pdo->exec("ALTER TABLE event_media MODIFY file_url VARCHAR(500) NOT NULL");
    }

    if (!isset($columns['type'])) {
        $pdo->exec("ALTER TABLE event_media ADD COLUMN type ENUM('image','video') NOT NULL DEFAULT 'image'");
        if (isset($columns['media_type'])) {
            $pdo->exec("UPDATE event_media SET type = media_type");
        }
    }

    if (!isset($columns['sort_order'])) {
        $pdo->exec("ALTER TABLE event_media ADD COLUMN sort_order INT(11) NOT NULL DEFAULT 0");
    }

    $ready = true;
}

function eventMediaRows(PDO $pdo, int $eventId): array {
    ensureEventMediaTable($pdo);
    $stmt = $pdo->prepare("SELECT id, event_id, file_url, type, title_bn, title_en, sort_order
                           FROM event_media
                           WHERE event_id = ?
                           ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$eventId]);
    return $stmt->fetchAll();
}

// ============================================================
/**
 * Ensure dashboard-required tables exist.
 * This keeps the dashboard functional even if an older working
 * database dump did not contain teachers/courses tables.
 */
function ensureDashboardTables(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name_bn VARCHAR(150) DEFAULT NULL,
        name_en VARCHAR(150) DEFAULT NULL,
        deg_bn VARCHAR(150) DEFAULT NULL,
        deg_en VARCHAR(150) DEFAULT NULL,
        dept_bn VARCHAR(150) DEFAULT NULL,
        dept_en VARCHAR(150) DEFAULT NULL,
        file_url VARCHAR(255) DEFAULT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_teachers_status (status),
        KEY idx_teachers_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS courses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        type VARCHAR(50) DEFAULT NULL,
        title_bn VARCHAR(255) DEFAULT NULL,
        title_en VARCHAR(255) DEFAULT NULL,
        level_bn VARCHAR(150) DEFAULT NULL,
        level_en VARCHAR(150) DEFAULT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_courses_type (type),
        KEY idx_courses_status (status),
        KEY idx_courses_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ready = true;
}

ensureDashboardTables($pdo);

// ============================================================
// ২. ক্যাপচা ইমেজ
// ============================================================
if ($action === 'get_captcha') {
    try {
        $fetcher = new EducationBoardResult();
        $fetcher->solveChallenge();
        $fetcher->get($fetcher->getBaseUrl() . '/v2/home');
        $img = $fetcher->getCaptchaImage();
        header('Content-Type: image/jpeg');
        echo $img;
    } catch (Throwable $e) {
        logError('Captcha Error: ' . $e->getMessage());
        http_response_code(500);
        echo 'ক্যাপচা লোড করা যায়নি';
    }
    exit;
}

// ============================================================
// ৩. রেজাল্ট ভেরিফাই
// ============================================================
if ($action === 'verify_result') {
    $input = getJsonInput();
    if (!$input) sendJsonResponse(['error' => 'Invalid JSON']);
    
    $board = Validator::sanitize($input['board'] ?? '');
    $year = Validator::sanitize($input['year'] ?? '');
    $roll = Validator::sanitize($input['roll'] ?? '');
    $registration = Validator::sanitize($input['registration'] ?? '');
    $captcha = Validator::sanitize($input['captcha'] ?? '');
    
    if (!$board || !$roll || !$registration || !$captcha) {
        sendJsonResponse(['error' => $msg('সব তথ্য পূরণ করুন।', 'Please complete all required fields.')]);
    }
    if (!Validator::year($year)) {
        sendJsonResponse(['error' => $msg('পাসের সন সঠিক নয়।', 'Invalid passing year.')]);
    }
    
    $stmt = $pdo->query("SELECT setting_json FROM settings WHERE setting_key = 'scraper_settings'");
    $row = $stmt->fetch();
    $settings = $row ? json_decode($row['setting_json'], true) : [];
    $eligibility = $settings['eligibility'] ?? [];
    $minYear = (int)($eligibility['min_year'] ?? 2022);
    $maxYear = (int)($eligibility['max_year'] ?? 2026);
    $minGpa = (float)($eligibility['min_gpa'] ?? 2.00);
    $gpaOperator = $eligibility['gpa_operator'] ?? '>=';
    
    if ((int)$year < $minYear || (int)$year > $maxYear) {
        sendJsonResponse(['error' => $msg("শুধুমাত্র {$minYear}–{$maxYear} সালের আবেদন গ্রহণ করা হয়।", "Only applications from {$minYear}–{$maxYear} are accepted.")]);
    }
    
    try {
        $fetcher = new EducationBoardResult();
        $result = $fetcher->fetchResult($board, $year, $roll, $registration, $captcha, 'ssc');
    } catch (Exception $e) {
        sendJsonResponse(['error' => $e->getMessage()]);
    }
    
    $status = $result['official_status'] ?? 'UNKNOWN';
    $gpa = $result['gpa'] ?? null;
    if ($status !== 'PASSED') {
        sendJsonResponse(['error' => $msg('শিক্ষার্থী পাস করেননি।', 'The student did not pass.')]);
    }
    $eligible = false;
    if ($gpa !== null) {
        switch ($gpaOperator) {
            case '>=': $eligible = ($gpa >= $minGpa); break;
            case '>':  $eligible = ($gpa > $minGpa); break;
            default:   $eligible = false;
        }
    }
    if (!$eligible) {
        sendJsonResponse(['error' => $msg("ন্যূনতম GPA {$minGpa} প্রয়োজন, প্রাপ্ত {$gpa}।", "Minimum GPA {$minGpa} is required; received {$gpa}.")]);
    }
    
    $result['eligible'] = true;
    $result['min_gpa'] = $minGpa;
    $result['institution_name'] = $settings['institution']['name'] ?? '';
    $result['institution_logo'] = $settings['institution']['logo'] ?? '';
    $result['input_registration'] = $registration;
    sendJsonResponse(['success' => true, 'data' => $result]);
}

// ============================================================
// ৪. ডিপ্লোমা ভর্তি আবেদন
// ============================================================
if ($action === 'submit_diploma_admission') {
    $input = getJsonInput();
    if (!$input) sendJsonResponse(['error' => 'Invalid data']);
    $required = ['student_name','father_name','mother_name','phone','address','board','roll','registration','ssc_gpa','course_name','photo_path'];
    foreach ($required as $f) {
        if (empty($input[$f])) sendJsonResponse(['error' => $msg("{$f} ফিল্ডটি পূরণ করুন।", "Please complete the {$f} field.")]);
    }
    
    $stmt = $pdo->prepare("INSERT INTO admissions 
        (student_name, father_name, mother_name, phone, address, board, roll, registration, 
         ssc_gpa, course_type, course_name, photo_path, admission_type, verified, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'diploma', ?, ?, 'diploma', 1, 'pending')");
    $stmt->execute([
        Validator::sanitize($input['student_name']),
        Validator::sanitize($input['father_name']),
        Validator::sanitize($input['mother_name']),
        Validator::sanitize($input['phone']),
        Validator::sanitize($input['address']),
        Validator::sanitize($input['board']),
        Validator::sanitize($input['roll']),
        Validator::sanitize($input['registration']),
        Validator::sanitize($input['ssc_gpa']),
        Validator::sanitize($input['course_name']),
        Validator::sanitize($input['photo_path'])
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

// ============================================================
// ৫. NSDA ভর্তি আবেদন
// ============================================================
if ($action === 'submit_nsda_admission') {
    $input = getJsonInput();
    if (!$input) sendJsonResponse(['error' => 'Invalid data']);
    $required = ['student_name','father_name','mother_name','phone','address','course_name','photo_path','age','ssc_status'];
    foreach ($required as $f) {
        if (empty($input[$f])) sendJsonResponse(['error' => $msg("{$f} ফিল্ডটি পূরণ করুন।", "Please complete the {$f} field.")]);
    }
    if ((int)$input['age'] > 35) sendJsonResponse(['error' => $msg('সর্বোচ্চ বয়স ৩৫ বছর।', 'Maximum age is 35 years.')]);
    $sscStatus = in_array($input['ssc_status'], ['passed','failed','not_taken']) ? $input['ssc_status'] : 'not_taken';
    
    $stmt = $pdo->prepare("INSERT INTO admissions 
        (student_name, father_name, mother_name, phone, address, course_type, course_name, 
         photo_path, admission_type, ssc_status, age, status, fee) 
        VALUES (?, ?, ?, ?, ?, 'nsda', ?, ?, 'nsda', ?, ?, 'pending', 3500)");
    $stmt->execute([
        Validator::sanitize($input['student_name']),
        Validator::sanitize($input['father_name']),
        Validator::sanitize($input['mother_name']),
        Validator::sanitize($input['phone']),
        Validator::sanitize($input['address']),
        Validator::sanitize($input['course_name']),
        Validator::sanitize($input['photo_path']),
        $sscStatus,
        (int)$input['age']
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

// ============================================================
// ৬. পিডিএফ ডাউনলোড
// ============================================================
if ($action === 'download_pdf') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); exit; }
    $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    if (!$data) { http_response_code(404); exit; }
    
    $settingsStmt = $pdo->query("SELECT setting_json FROM settings WHERE setting_key = 'scraper_settings'");
    $row = $settingsStmt->fetch();
    $settings = $row ? json_decode($row['setting_json'], true) : [];
    $logo = '../' . ($settings['institution']['logo'] ?? 'assets/images/ASPI-Logo.png');
    $address = $settings['institution']['short_name'] ?? '';
    $instituteName = $settings['institution']['name'] ?? ($lang === 'en' ? 'Ashab Siraj Polytechnic Institute' : 'আসহাব সিরাজ পলিটেকনিক ইনস্টিটিউট');
    
    $pdfData = [
        'logo_path' => $logo,
        'address' => $address,
        'institution_name' => $instituteName,
        'student_name' => $data['student_name'],
        'father_name' => $data['father_name'],
        'mother_name' => $data['mother_name'],
        'phone' => $data['phone'],
        'address' => $data['address'],
        'board' => $data['board'] ?? '',
        'roll' => $data['roll'] ?? '',
        'registration' => $data['registration'] ?? '',
        'ssc_gpa' => $data['ssc_gpa'] ?? '',
        'course_type' => $data['course_type'],
        'course_name' => $data['course_name'],
        'photo_path' => '../' . $data['photo_path'],
        'fee' => $data['fee'] ?? '',
        'status' => $data['status'] ?? 'pending',
        'admission_type' => $data['admission_type'] ?? 'diploma'
    ];
    
    $pdf = PdfGenerator::generateAdmissionPDF($pdfData);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="admission_'.$id.'.pdf"');
    echo $pdf;
    exit;
}

// ============================================================
// ৭. অ্যাডমিন অ্যাকশন: স্ট্যাটাস আপডেট
// ============================================================
if ($action === 'update_admission_status') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    if (!$input || !isset($input['id']) || !isset($input['status'])) {
        sendJsonResponse(['error' => 'Invalid data']);
    }
    $allowed = ['pending','accepted','rejected','completed'];
    if (!in_array($input['status'], $allowed)) sendJsonResponse(['error' => 'Invalid status']);
    
    $stmt = $pdo->prepare("UPDATE admissions SET status = ?, admin_note = ? WHERE id = ?");
    $stmt->execute([$input['status'], $input['note'] ?? '', (int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ৮. অ্যাডমিন: আবেদন এডিট
// ============================================================
if ($action === 'edit_admission') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    if (!$input || !isset($input['id'])) sendJsonResponse(['error' => 'Invalid data']);
    $stmt = $pdo->prepare("UPDATE admissions SET phone = ?, address = ?, course_name = ? WHERE id = ?");
    $stmt->execute([
        Validator::sanitize($input['phone'] ?? ''),
        Validator::sanitize($input['address'] ?? ''),
        Validator::sanitize($input['course_name'] ?? ''),
        (int)$input['id']
    ]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ৯. উক্তি (Quotes) CRUD
// ============================================================
if ($action === 'get_quotes') {
    $stmt = $pdo->query("SELECT id, name_bn, name_en, name$suffix as name, designation_bn, designation_en, designation$suffix as designation, quote_bn, quote_en, quote$suffix as quote, image_url, status, sort_order FROM quotes ORDER BY sort_order ASC, id ASC");
    sendJsonResponse(['quotes' => $stmt->fetchAll()]);
}

if ($action === 'add_quote') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("INSERT INTO quotes (name_bn, name_en, designation_bn, designation_en, quote_bn, quote_en, image_url, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['name_bn'] ?? '', $input['name_en'] ?? '',
        $input['designation_bn'] ?? '', $input['designation_en'] ?? '',
        $input['quote_bn'] ?? '', $input['quote_en'] ?? '',
        $input['image_url'] ?? '', $input['status'] ?? 1, $input['sort_order'] ?? 0
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

if ($action === 'update_quote') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("UPDATE quotes SET name_bn=?, name_en=?, designation_bn=?, designation_en=?, quote_bn=?, quote_en=?, image_url=?, status=?, sort_order=? WHERE id=?");
    $stmt->execute([
        $input['name_bn'] ?? '', $input['name_en'] ?? '',
        $input['designation_bn'] ?? '', $input['designation_en'] ?? '',
        $input['quote_bn'] ?? '', $input['quote_en'] ?? '',
        $input['image_url'] ?? '', $input['status'] ?? 1, $input['sort_order'] ?? 0,
        (int)$input['id']
    ]);
    sendJsonResponse(['status' => 'success']);
}

if ($action === 'delete_quote') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = ?");
    $stmt->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১০. সোশ্যাল মিডিয়া লিংক CRUD
// ============================================================
if ($action === 'get_social_links') {
    $isAdminRequest = !empty($_GET['admin']) && $_GET['admin'] === '1' && !empty($_SESSION['admin_logged_in']);
    $stmt = $pdo->query($isAdminRequest ? "SELECT * FROM social_links ORDER BY sort_order ASC, id ASC" : "SELECT * FROM social_links WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    sendJsonResponse(['social_links' => $stmt->fetchAll()]);
}

if ($action === 'add_social_link') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("INSERT INTO social_links (platform_name, platform_name_bn, icon_class, icon_image, url, color, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['platform_name'] ?? '', $input['platform_name_bn'] ?? '',
        $input['icon_class'] ?? '', $input['icon_image'] ?? '',
        $input['url'] ?? '', $input['color'] ?? '#000000',
        $input['sort_order'] ?? 0, $input['status'] ?? 1
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

if ($action === 'update_social_link') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("UPDATE social_links SET platform_name=?, platform_name_bn=?, icon_class=?, icon_image=?, url=?, color=?, sort_order=?, status=? WHERE id=?");
    $stmt->execute([
        $input['platform_name'] ?? '', $input['platform_name_bn'] ?? '',
        $input['icon_class'] ?? '', $input['icon_image'] ?? '',
        $input['url'] ?? '', $input['color'] ?? '#000000',
        $input['sort_order'] ?? 0, $input['status'] ?? 1,
        (int)$input['id']
    ]);
    sendJsonResponse(['status' => 'success']);
}

if ($action === 'delete_social_link') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("DELETE FROM social_links WHERE id = ?");
    $stmt->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১১. গ্যালারি অ্যাটাচমেন্ট
// ============================================================
if ($action === 'get_gallery_attachments') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, file_url, title$suffix as title, type, sort_order FROM gallery_attachments WHERE gallery_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$id]);
    sendJsonResponse(['attachments' => $stmt->fetchAll()]);
}

if ($action === 'upload_gallery_attachment') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $gallery_id = (int)($_POST['gallery_id'] ?? 0);
    if (!isset($_FILES['file'])) sendJsonResponse(['error' => 'No file']);
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $targetDir = "assets/uploads/gallery/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $newName = time() . '_' . rand(1000,9999) . '.' . $ext;
    $target = $targetDir . $newName;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image' : (in_array($ext, ['mp4','webm','ogg']) ? 'video' : 'pdf');
        $stmt = $pdo->prepare("INSERT INTO gallery_attachments (gallery_id, file_url, type, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$gallery_id, $target, $type, 0]);
        sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId(), 'url' => $target]);
    } else {
        sendJsonResponse(['error' => 'Upload failed']);
    }
}

if ($action === 'delete_gallery_attachment') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("DELETE FROM gallery_attachments WHERE id = ?");
    $stmt->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১২. গ্যালারি আইটেম CRUD
// ============================================================
if ($action === 'get_gallery_items') {
    $isAdminRequest = !empty($_GET['admin']) && $_GET['admin'] === '1' && !empty($_SESSION['admin_logged_in']);
    $where = $isAdminRequest ? '' : ' WHERE status = 1';
    $stmt = $pdo->query("SELECT id, title_bn, title_en, title$suffix as title, file_url, thumbnail, status, sort_order FROM gallery_items" . $where . " ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM gallery_attachments WHERE gallery_id = ?");
        $stmt2->execute([$item['id']]);
        $item['attachments_count'] = (int)$stmt2->fetchColumn();
    }
    sendJsonResponse(['gallery' => $items]);
}

if ($action === 'upload_gallery_item') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    if (!isset($_FILES['file'])) sendJsonResponse(['error' => 'No file']);
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $targetDir = "assets/uploads/gallery/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $newName = time() . '_' . rand(1000,9999) . '.' . $ext;
    $target = $targetDir . $newName;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $title_bn = $_POST['title_bn'] ?? '';
        $title_en = $_POST['title_en'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO gallery_items (title_bn, title_en, file_url, thumbnail, status, sort_order) VALUES (?, ?, ?, ?, 1, 0)");
        $stmt->execute([$title_bn, $title_en, $target, $target]);
        sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
    } else {
        sendJsonResponse(['error' => 'Upload failed']);
    }
}

if ($action === 'update_gallery_item') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("UPDATE gallery_items SET title_bn=?, title_en=?, status=?, sort_order=? WHERE id=?");
    $stmt->execute([
        $input['title_bn'] ?? '', $input['title_en'] ?? '',
        $input['status'] ?? 1, $input['sort_order'] ?? 0,
        (int)$input['id']
    ]);
    sendJsonResponse(['status' => 'success']);
}

if ($action === 'delete_gallery_item') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $pdo->prepare("DELETE FROM gallery_attachments WHERE gallery_id = ?")->execute([(int)$input['id']]);
    $stmt = $pdo->prepare("DELETE FROM gallery_items WHERE id = ?");
    $stmt->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৩. বিজ্ঞপ্তি ক্যাটাগরি CRUD
// ============================================================
if ($action === 'get_notice_categories') {
    $stmt = $pdo->query("SELECT id, parent_id, name_bn, name_en, name$suffix as name, slug, status, sort_order FROM notice_categories ORDER BY parent_id, sort_order, id");
    sendJsonResponse(['categories' => $stmt->fetchAll()]);
}

if ($action === 'add_notice_category') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("INSERT INTO notice_categories (parent_id, name_bn, name_en, slug, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['parent_id'] ?? 0,
        $input['name_bn'] ?? '', $input['name_en'] ?? '',
        $input['slug'] ?? '', $input['status'] ?? 1, $input['sort_order'] ?? 0
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

if ($action === 'delete_notice_category') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $pdo->prepare("DELETE FROM notice_categories WHERE id = ?")->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৪. ইভেন্ট/নিউজ মিডিয়া
// ============================================================
if ($action === 'get_event_media') {
    $id = (int)($_GET['id'] ?? 0);
    sendJsonResponse(['media' => eventMediaRows($pdo, $id)]);
}

if ($action === 'upload_event_media') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    ensureEventMediaTable($pdo);
    $eventId = (int)($_POST['event_id'] ?? 0);
    if ($eventId <= 0) sendJsonResponse(['error' => $msg('ইভেন্ট/নিউজ নির্বাচন করুন।', 'A valid event/news item is required.')]);
    $check = $pdo->prepare('SELECT id FROM events WHERE id = ? LIMIT 1');
    $check->execute([$eventId]);
    if (!$check->fetchColumn()) sendJsonResponse(['error' => $msg('ইভেন্ট/নিউজ পাওয়া যায়নি।', 'Event/news item not found.')], 404);
    if (empty($_FILES['files'])) sendJsonResponse(['error' => $msg('কোনো মিডিয়া নির্বাচন করা হয়নি।', 'No media selected.')]);

    $targetDir = 'assets/uploads/events/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    $created = [];
    $allowedExt = ['jpg','jpeg','png','gif','webp','mp4','webm','ogg'];
    $allowedMime = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm','video/ogg'];

    for ($i=0; $i<$count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $tmp = $files['tmp_name'][$i];
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $mime = function_exists('mime_content_type') ? (mime_content_type($tmp) ?: '') : '';
        if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) continue;
        if (($files['size'][$i] ?? 0) > 30*1024*1024) continue;
        $type = str_starts_with($mime, 'video/') ? 'video' : 'image';
        $newName = bin2hex(random_bytes(8)) . '_' . time() . '_' . $i . '.' . $ext;
        $target = $targetDir . $newName;
        if (move_uploaded_file($tmp, $target)) {
            $stmt = $pdo->prepare('INSERT INTO event_media (event_id, file_url, type, sort_order) VALUES (?, ?, ?, ?)');
            $stmt->execute([$eventId, $target, $type, $i]);
            $created[] = ['id' => (int)$pdo->lastInsertId(), 'event_id' => $eventId, 'file_url' => $target, 'type' => $type, 'sort_order' => $i];
        }
    }
    if (!$created) sendJsonResponse(['error' => $msg('কোনো বৈধ ছবি/ভিডিও আপলোড হয়নি।', 'No valid image/video was uploaded.')]);
    sendJsonResponse(['status' => 'success', 'media' => $created]);
}

if ($action === 'delete_event_media') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    ensureEventMediaTable($pdo);
    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT file_url FROM event_media WHERE id = ?');
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    $pdo->prepare('DELETE FROM event_media WHERE id = ?')->execute([$id]);
    if ($file && is_file($file)) @unlink($file);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৫. ইভেন্ট/নিউজ CRUD
// ============================================================
if ($action === 'save_event') {
    ensureEventMediaTable($pdo);
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);
    $type = $input['type'] ?? 'event';
    $stmt = $pdo->prepare("INSERT INTO events 
        (id, date, category_bn, category_en, title_bn, title_en, description_bn, description_en, 
         content_bn, content_en, file_url, type, showInMarquee, status, sort_order) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        date=VALUES(date), category_bn=VALUES(category_bn), category_en=VALUES(category_en),
        title_bn=VALUES(title_bn), title_en=VALUES(title_en),
        description_bn=VALUES(description_bn), description_en=VALUES(description_en),
        content_bn=VALUES(content_bn), content_en=VALUES(content_en),
        file_url=VALUES(file_url), type=VALUES(type), showInMarquee=VALUES(showInMarquee),
        status=VALUES(status), sort_order=VALUES(sort_order)");
    $stmt->execute([
        $id ?: null,
        $input['date'] ?? null,
        $input['category_bn'] ?? '', $input['category_en'] ?? '',
        $input['title_bn'] ?? '', $input['title_en'] ?? '',
        $input['description_bn'] ?? '', $input['description_en'] ?? '',
        $input['content_bn'] ?? '', $input['content_en'] ?? '',
        $input['file_url'] ?? '', $type,
        $input['showInMarquee'] ?? 0, $input['status'] ?? 1, $input['sort_order'] ?? 0
    ]);
    $newId = $pdo->lastInsertId() ?: $id;
    sendJsonResponse(['status' => 'success', 'id' => $newId, 'media' => eventMediaRows($pdo, (int)$newId)]);
}

if ($action === 'delete_event') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    ensureEventMediaTable($pdo);
    $input = getJsonInput();
    $eventId = (int)($input['id'] ?? 0);
    $media = eventMediaRows($pdo, $eventId);
    $pdo->prepare('DELETE FROM event_media WHERE event_id = ?')->execute([$eventId]);
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
    foreach ($media as $m) { if (!empty($m['file_url']) && is_file($m['file_url'])) @unlink($m['file_url']); }
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৫. কন্টেন্ট ম্যানেজমেন্ট
// ============================================================
if ($action === 'get_content') {
    $stmt = $pdo->query("SELECT * FROM content WHERE 1");
    $content = [];
    while ($row = $stmt->fetch()) {
        $content[$row['content_key']] = $row['content_value'];
    }
    sendJsonResponse(['content' => $content]);
}

if ($action === 'save_content') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("INSERT INTO content (content_key, content_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE content_value = ?");
    foreach ($input as $key => $value) {
        $stmt->execute([$key, $value, $value]);
    }
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৬. FAQ CRUD
// ============================================================
if ($action === 'get_faqs') {
    $stmt = $pdo->query("SELECT id, question_bn, question_en, question$suffix as question, answer_bn, answer_en, answer$suffix as answer, status, sort_order FROM faqs ORDER BY sort_order ASC, id ASC");
    sendJsonResponse(['faqs' => $stmt->fetchAll()]);
}

if ($action === 'add_faq') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("INSERT INTO faqs (question_bn, question_en, answer_bn, answer_en, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['question_bn'] ?? '', $input['question_en'] ?? '',
        $input['answer_bn'] ?? '', $input['answer_en'] ?? '',
        $input['status'] ?? 1, $input['sort_order'] ?? 0
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

if ($action === 'delete_faq') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৭. সেটিংস আপডেট (পপআপ সহ)
// ============================================================
if ($action === 'update_settings') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $allowed = ['marquee_speed', 'popup_animation_delay', 'popup_images', 'popup_enabled'];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    foreach ($input as $key => $value) {
        if (in_array($key, $allowed)) {
            if ($key === 'popup_images' && is_array($value)) {
                $value = json_encode($value);
            }
            $stmt->execute([$key, $value, $value]);
        }
    }
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৮. ভর্তি সেটিংস আপডেট
// ============================================================
if ($action === 'update_admission_settings') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $keys = ['master_admission', 'diploma_admission', 'nsda_admission'];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    foreach ($keys as $key) {
        $val = isset($input[$key]) ? ($input[$key] ? '1' : '0') : '1';
        $stmt->execute([$key, $val, $val]);
    }
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ১৯. ক্লিনআপ সিস্টেম
// ============================================================
if ($action === 'cleanup') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $type = $input['type'] ?? '';
    
    switch ($type) {
        case 'old_admissions':
            $pdo->exec("DELETE FROM admissions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR) AND status != 'pending'");
            break;
        case 'duplicate_notices':
            $pdo->exec("DELETE n1 FROM notices n1 INNER JOIN notices n2 WHERE n1.id > n2.id AND n1.title_bn = n2.title_bn AND n1.date = n2.date");
            break;
        case 'orphan_attachments':
            $pdo->exec("DELETE FROM gallery_attachments WHERE gallery_id NOT IN (SELECT id FROM gallery_items)");
            break;
        case 'all':
            $pdo->exec("DELETE FROM admissions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR) AND status != 'pending'");
            $pdo->exec("DELETE n1 FROM notices n1 INNER JOIN notices n2 WHERE n1.id > n2.id AND n1.title_bn = n2.title_bn AND n1.date = n2.date");
            $pdo->exec("DELETE FROM gallery_attachments WHERE gallery_id NOT IN (SELECT id FROM gallery_items)");
            break;
        default:
            sendJsonResponse(['error' => 'Invalid cleanup type']);
    }
    sendJsonResponse(['status' => 'success', 'message' => 'Cleanup completed']);
}

// ============================================================
// ২০. ইউজার ম্যানেজমেন্ট
// ============================================================
if ($action === 'get_users') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $stmt = $pdo->query("SELECT id, username, full_name, email, role, status, last_login FROM users ORDER BY id");
    sendJsonResponse(['users' => $stmt->fetchAll()]);
}

if ($action === 'add_user') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $hash = password_hash($input['password'] ?? 'password123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['username'] ?? '', $hash,
        $input['full_name'] ?? '', $input['email'] ?? '',
        $input['role'] ?? 'editor', 'active'
    ]);
    sendJsonResponse(['status' => 'success', 'id' => $pdo->lastInsertId()]);
}

if ($action === 'toggle_user_status') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

if ($action === 'delete_user') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    if ((int)$input['id'] == 1) sendJsonResponse(['error' => 'Cannot delete main admin']);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$input['id']]);
    sendJsonResponse(['status' => 'success']);
}

// ============================================================
// ২১. জেনেরিক ডেটা GET
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action)) {
    $response = [];
    $isAdminRequest = !empty($_GET['admin']) && $_GET['admin'] === '1' && !empty($_SESSION['admin_logged_in']);
    
    // Site settings
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $val = $row['setting_value'];
        if ($row['setting_key'] == 'admissionOpen') $val = (bool)$val;
        if ($row['setting_key'] == 'master_admission') $val = (bool)$val;
        if ($row['setting_key'] == 'diploma_admission') $val = (bool)$val;
        if ($row['setting_key'] == 'nsda_admission') $val = (bool)$val;
        if ($row['setting_key'] == 'marquee_speed') $val = (int)$val;
        if ($row['setting_key'] == 'popup_animation_delay') $val = (int)$val;
        if ($row['setting_key'] == 'popup_enabled') $val = (bool)$val;
        if ($row['setting_key'] == 'popup_images') {
            $val = json_decode($val, true) ?: [];
        }
        $settings[$row['setting_key']] = $val;
    }
    $response['site'] = $settings;
    
    // Scraper settings
    $scStmt = $pdo->query("SELECT setting_json FROM settings WHERE setting_key = 'scraper_settings'");
    $scRow = $scStmt->fetch();
    $response['scraper'] = $scRow ? json_decode($scRow['setting_json'], true) : [];
    
    // Dynamic data (language-aware)
    $response['messages'] = $pdo->query("SELECT id, name_bn, name_en, name$suffix as name, designation_bn, designation_en, designation$suffix as designation, message_bn, message_en, message$suffix as message, image_url FROM messages ORDER BY id ASC")->fetchAll();
    $response['notices'] = $pdo->query("SELECT id, date, date_bn, date_en, category_id, sub_category_id, title_bn, title_en, title$suffix as title, file_url, isNew, showInMarquee FROM notices ORDER BY id DESC")->fetchAll();
    ensureEventMediaTable($pdo);
    $eventRows = $pdo->query("SELECT * FROM events WHERE type = 'event' ORDER BY sort_order ASC")->fetchAll();
    foreach ($eventRows as &$eventRow) {
        $eventRow['title'] = $eventRow['title' . $suffix] ?? '';
        $eventRow['description'] = $eventRow['description' . $suffix] ?? '';
        $eventRow['content'] = $eventRow['content' . $suffix] ?? '';
    }
    unset($eventRow);
    foreach ($eventRows as &$eventRow) { $eventRow['media'] = eventMediaRows($pdo, (int)$eventRow['id']); }
    unset($eventRow);
    $newsRows = $pdo->query("SELECT * FROM events WHERE type = 'news' ORDER BY sort_order ASC")->fetchAll();
    foreach ($newsRows as &$newsRow) {
        $newsRow['title'] = $newsRow['title' . $suffix] ?? '';
        $newsRow['description'] = $newsRow['description' . $suffix] ?? '';
        $newsRow['content'] = $newsRow['content' . $suffix] ?? '';
    }
    unset($newsRow);
    foreach ($newsRows as &$newsRow) { $newsRow['media'] = eventMediaRows($pdo, (int)$newsRow['id']); }
    unset($newsRow);
    $response['events'] = $eventRows;
    $response['news'] = $newsRows;
    $response['teachers'] = $pdo->query("SELECT id, name_bn, name_en, name$suffix as name, deg_bn, deg_en, deg$suffix as deg, dept_bn, dept_en, dept$suffix as dept, file_url, status, sort_order FROM teachers ORDER BY id DESC")->fetchAll();
    $response['courses'] = $pdo->query("SELECT id, type, title_bn, title_en, title$suffix as title, level_bn, level_en, level$suffix as level, status, sort_order FROM courses ORDER BY id ASC")->fetchAll();
    $response['admissions'] = $isAdminRequest ? $pdo->query("SELECT * FROM admissions ORDER BY id DESC")->fetchAll() : [];
    
    // Quotes
    $response['quotes'] = $pdo->query("SELECT id, name_bn, name_en, name$suffix as name, designation_bn, designation_en, designation$suffix as designation, quote_bn, quote_en, quote$suffix as quote, image_url, status, sort_order FROM quotes ORDER BY sort_order ASC, id ASC")->fetchAll();
    
    // Social links
    $response['social_links'] = $isAdminRequest ? $pdo->query("SELECT * FROM social_links ORDER BY sort_order ASC, id ASC")->fetchAll() : $pdo->query("SELECT * FROM social_links WHERE status = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
    
    // Notice categories
    $response['notice_categories'] = $pdo->query("SELECT id, parent_id, name_bn, name_en, name$suffix as name, slug, status, sort_order FROM notice_categories ORDER BY parent_id, sort_order, id")->fetchAll();
    
    // Gallery items with count
    $galleryWhere = $isAdminRequest ? '' : ' WHERE status = 1';
    $galleryItems = $pdo->query("SELECT id, title_bn, title_en, title$suffix as title, file_url, thumbnail, status, sort_order FROM gallery_items" . $galleryWhere . " ORDER BY sort_order ASC, id ASC")->fetchAll();
    foreach ($galleryItems as &$item) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM gallery_attachments WHERE gallery_id = ?");
        $stmt2->execute([$item['id']]);
        $item['attachments_count'] = (int)$stmt2->fetchColumn();
    }
    $response['gallery'] = $galleryItems;
    
    // Content
    $contentStmt = $pdo->query("SELECT * FROM content");
    $content = [];
    while ($row = $contentStmt->fetch()) {
        $content[$row['content_key']] = $row['content_value'];
    }
    $response['content'] = $content;
    
    // FAQs
    $response['faqs'] = $pdo->query("SELECT id, question_bn, question_en, question$suffix as question, answer_bn, answer_en, answer$suffix as answer, status, sort_order FROM faqs ORDER BY sort_order ASC, id ASC")->fetchAll();
    
    sendJsonResponse($response);
}

// ============================================================
// ২২. ডেটা সেভ (POST) - অ্যাডমিন
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error' => 'Unauthorized'], 401);
    $input = getJsonInput();
    if (!$input) sendJsonResponse(["error" => "Invalid JSON"]);

    /**
     * Synchronize a dashboard-managed table without deleting/reinserting
     * the whole table. Existing IDs remain stable, which is essential for
     * event_media -> events and for edit/delete operations.
     * New client-side Date.now() IDs are ignored and real AUTO_INCREMENT IDs
     * are generated by MySQL.
     */
    $syncTable = function(string $table, array $cols, array $rows) use ($pdo): void {
        $safeTables = ['messages','notices','events','teachers','courses','quotes','social_links','notice_categories','faqs'];
        if (!in_array($table, $safeTables, true)) throw new Exception('Invalid table');
        $existingIds = [];
        $idRows = $pdo->query("SELECT id FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($idRows as $id) $existingIds[(int)$id] = true;
        $keepIds = [];

        foreach ($rows as $row) {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : 0;
            $validExistingId = $id > 0 && $id <= 2147483647 && isset($existingIds[$id]);
            if ($validExistingId) {
                $sets = implode(',', array_map(fn($c) => "`{$c}` = ?", $cols));
                $values = array_map(fn($c) => $row[$c] ?? null, $cols);
                $values[] = $id;
                $stmt = $pdo->prepare("UPDATE {$table} SET {$sets} WHERE id = ?");
                $stmt->execute($values);
                $keepIds[] = $id;
            } else {
                $fields = implode(',', array_map(fn($c) => "`{$c}`", $cols));
                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $values = array_map(fn($c) => $row[$c] ?? null, $cols);
                $stmt = $pdo->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");
                $stmt->execute($values);
                $keepIds[] = (int)$pdo->lastInsertId();
            }
        }

        // Only remove records that the dashboard explicitly removed.
        if ($keepIds) {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            if ($table === 'events') {
                $old = $pdo->prepare("SELECT id FROM events WHERE id NOT IN ({$placeholders})");
                $old->execute($keepIds);
                $deletedEventIds = array_map('intval', $old->fetchAll(PDO::FETCH_COLUMN));
                if ($deletedEventIds) {
                    $mp = implode(',', array_fill(0, count($deletedEventIds), '?'));
                    $pdo->prepare("DELETE FROM event_media WHERE event_id IN ({$mp})")->execute($deletedEventIds);
                }
            }
            $pdo->prepare("DELETE FROM {$table} WHERE id NOT IN ({$placeholders})")->execute($keepIds);
        } else {
            if ($table === 'events') $pdo->exec("DELETE FROM event_media");
            $pdo->exec("DELETE FROM {$table}");
        }
    };

    try {
        $pdo->beginTransaction();

        if (isset($input['site'])) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($input['site'] as $key => $value) {
                if (in_array($key, ['master_admission','diploma_admission','nsda_admission','admissionOpen','popup_enabled'], true)) $value = $value ? '1' : '0';
                if ($key === 'popup_images' && is_array($value)) $value = json_encode($value, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                $stmt->execute([$key, $value]);
            }
        }
        if (isset($input['scraper'])) {
            $json=json_encode($input['scraper'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $stmt=$pdo->prepare("INSERT INTO settings (setting_key, setting_json) VALUES ('scraper_settings', ?) ON DUPLICATE KEY UPDATE setting_json=VALUES(setting_json)");
            $stmt->execute([$json]);
        }

        $tables=[
            'messages'=>['name_bn','name_en','designation_bn','designation_en','message_bn','message_en','image_url'],
            'notices'=>['date','date_bn','date_en','category_id','sub_category_id','title_bn','title_en','file_url','isNew','showInMarquee'],
            'events'=>['date','category_bn','category_en','title_bn','title_en','description_bn','description_en','content_bn','content_en','file_url','type','showInMarquee','status','sort_order'],
            'teachers'=>['name_bn','name_en','deg_bn','deg_en','dept_bn','dept_en','file_url','status','sort_order'],
            'courses'=>['type','title_bn','title_en','level_bn','level_en','status','sort_order'],
            'quotes'=>['name_bn','name_en','designation_bn','designation_en','quote_bn','quote_en','image_url','status','sort_order'],
            'social_links'=>['platform_name','platform_name_bn','icon_class','icon_image','url','color','sort_order','status'],
            'notice_categories'=>['parent_id','name_bn','name_en','slug','status','sort_order'],
            'faqs'=>['question_bn','question_en','answer_bn','answer_en','status','sort_order']
        ];
        foreach($tables as $table=>$cols){ if(isset($input[$table]) && is_array($input[$table])) $syncTable($table,$cols,$input[$table]); }

        if (isset($input['content']) && is_array($input['content'])) {
            $stmt=$pdo->prepare("INSERT INTO content (content_key,content_value) VALUES (?,?) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value)");
            foreach($input['content'] as $key=>$value) $stmt->execute([$key,$value]);
        }
        if (isset($input['gallery']) && is_array($input['gallery'])) {
            $existing=$pdo->query("SELECT id FROM gallery_items")->fetchAll(PDO::FETCH_COLUMN); $keep=[];
            foreach($input['gallery'] as $g){
                $id=(isset($g['id'])&&is_numeric($g['id']))?(int)$g['id']:0;
                if($id>0 && in_array($id,array_map('intval',$existing),true)){
                    $pdo->prepare("UPDATE gallery_items SET title_bn=?,title_en=?,status=?,sort_order=? WHERE id=?")->execute([$g['title_bn']??'',$g['title_en']??'',$g['status']??1,$g['sort_order']??0,$id]); $keep[]=$id;
                } elseif(!empty($g['file_url'])) {
                    $stmt=$pdo->prepare("INSERT INTO gallery_items(title_bn,title_en,file_url,thumbnail,status,sort_order) VALUES(?,?,?,?,?,?)");
                    $stmt->execute([$g['title_bn']??'',$g['title_en']??'',$g['file_url'],$g['thumbnail']??$g['file_url'],$g['status']??1,$g['sort_order']??0]); $keep[]=(int)$pdo->lastInsertId();
                }
            }
            if($keep){$ph=implode(',',array_fill(0,count($keep),'?'));$pdo->prepare("DELETE FROM gallery_items WHERE id NOT IN ({$ph})")->execute($keep);}
        }

        $pdo->commit();
        sendJsonResponse(["status"=>"success"]);
    } catch (Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        logError('Save Error: '.$e->getMessage());
        sendJsonResponse(["error"=>"Database Error: ".$e->getMessage()],500);
    }
}

// ============================================================
// ২৩. মার্ক রিড
// ============================================================
if ($action === 'mark_read') {
    $pdo->exec("UPDATE admissions SET is_read = 1 WHERE is_read = 0");
    sendJsonResponse(['status' => 'success']);
}


if ($action === 'dashboard_health') {
    if (empty($_SESSION['admin_logged_in'])) sendJsonResponse(['error'=>'Unauthorized'],401);
    $tables=['admissions','messages','notices','events','teachers','courses','quotes','social_links','notice_categories','gallery_items','gallery_attachments','content','faqs','settings','users'];
    $checks=[]; $ok=true;
    foreach($tables as $table){
        try{$pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");$checks[$table]=true;}catch(Throwable $e){$checks[$table]=false;$ok=false;}
    }
    sendJsonResponse(['status'=>$ok?'ok':'error','tables'=>$checks]);
}

// ============================================================
// ২৪. লগইন চেক
// ============================================================
if ($action === 'check_login') {
    $loggedIn = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    sendJsonResponse(['logged_in' => $loggedIn, 'user' => $_SESSION['admin_user'] ?? null]);
}

http_response_code(404);
sendJsonResponse(['error' => 'Invalid API action']);