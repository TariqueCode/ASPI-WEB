<?php
class EducationBoardResult {
    private $baseUrl = 'https://www.educationboardresults.gov.bd';
    private $cookieFile;
    private $ch;

    public function __construct() {
        $this->cookieFile = __DIR__ . '/../storage/cookies/edu_board_cookies.txt';
        if (!file_exists(dirname($this->cookieFile))) {
            mkdir(dirname($this->cookieFile), 0777, true);
        }
        $this->initCurl();
    }

    private function initCurl() {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 60,
        ]);
    }

    public function getBaseUrl() {
        return $this->baseUrl;
    }

    public function get($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, false);
        return curl_exec($this->ch);
    }

    public function post($url, $data, $json = false) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, true);
        if ($json) {
            $data = json_encode($data);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            if (is_array($data)) {
                $data = http_build_query($data);
            }
        }
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        return curl_exec($this->ch);
    }

    public function solveChallenge() {
        $html = $this->get($this->baseUrl . '/v2/home');
        if (!$html) {
            throw new Exception('চ্যালেঞ্জ পেজ লোড করা যায়নি');
        }

        if (!preg_match('/var\s+challenge\s*=\s*(\{.*?\});/s', $html, $matches)) {
            return true;
        }

        $challenge = json_decode($matches[1], true);
        if (!$challenge || !isset($challenge['token']) || !isset($challenge['missing']['id'])) {
            throw new Exception('চ্যালেঞ্জ ডেটা পার্স করা যায়নি');
        }

        $answer = 'puzzle:' . $challenge['missing']['id'];

        $response = $this->post(
            $this->baseUrl . '/_challenge-verify',
            ['token' => $challenge['token'], 'answer' => $answer],
            true
        );

        $result = json_decode($response, true);
        if (!$result || !isset($result['ok']) || $result['ok'] !== true) {
            throw new Exception('চ্যালেঞ্জ ভেরিফিকেশন ব্যর্থ হয়েছে');
        }

        return true;
    }

    public function getCaptchaImage() {
        $this->solveChallenge();
        $this->get($this->baseUrl . '/v2/home');
        return $this->get($this->baseUrl . '/v2/captcha');
    }

    public function fetchResult($board, $year, $roll, $registration, $captcha, $exam = 'ssc') {
        $postData = [
            'board' => $board,
            'exam' => $exam,
            'year' => $year,
            'result_type' => '1',
            'roll' => $roll,
            'reg' => $registration,
            'captcha' => $captcha,
            'submit' => 'View Result'
        ];

        $response = $this->post($this->baseUrl . '/v2/getres', $postData);
        $json = json_decode($response, true);

        if (!$json || !isset($json['status']) || $json['status'] != 0) {
            $msg = isset($json['msg']) ? $json['msg'] : 'ফলাফল পাওয়া যায়নি';
            throw new Exception($msg);
        }

        return $this->parseResult($json);
    }

    private function parseResult($json) {
        $res = $json['res'] ?? [];
        $data = [];

        $data['name'] = $res['name'] ?? '';
        $data['father_name'] = $res['fname'] ?? '';
        $data['mother_name'] = $res['mname'] ?? '';
        $data['institution'] = $res['inst_name'] ?? '';
        $data['board'] = $res['board_name'] ?? '';
        $data['group'] = $res['stud_group'] ?? '';
        $data['roll'] = $res['roll_no'] ?? '';
        $data['registration'] = $res['regno'] ?? '';
        $data['session'] = $res['session'] ?? '';
        $data['dob'] = $res['dob'] ?? '';
        $data['gender'] = $res['stud_sex'] ?? '';
        $data['type'] = $res['stud_type'] ?? '';

        $data['gpa'] = $this->extractOfficialGPA($res);
        $data['official_status'] = $this->determineOfficialStatus($res, $data['gpa']);
        $data['subjects'] = $this->extractSubjects($json);

        return $data;
    }

    private function extractOfficialGPA($res) {
        if (isset($res['gpa']) && is_numeric($res['gpa'])) {
            return (float)$res['gpa'];
        }
        if (isset($res['res_detail']) && preg_match('/GPA\s*=\s*([\d.]+)/i', $res['res_detail'], $m)) {
            return (float)$m[1];
        }
        return null;
    }

    private function determineOfficialStatus($res, $officialGpa) {
    $resDetail = trim($res['res_detail'] ?? '');
    $upperDetail = strtoupper($resDetail);
    
    // প্রথমে GPA দিয়ে যাচাই (যদি GPA > 0 হয় তাহলে পাস)
    if ($officialGpa !== null && $officialGpa > 0.00) {
        // GPA > 0 হলে পাস ধরে নিন, তবে চেক করুন res_detail এ "FAIL" থাকলে সেটা উপেক্ষা করুন
        // কারণ অনেক সময় "FAIL" শুধু কোনো বিষয়ের জন্য হতে পারে, কিন্তু overall GPA পজিটিভ
        return 'PASSED';
    }
    
    // যদি GPA 0.00 হয় তাহলে ফেল
    if ($officialGpa !== null && $officialGpa == 0.00) {
        return 'FAILED';
    }
    
    // তারপর res_detail দেখুন
    if (strpos($upperDetail, 'PASS') !== false || strpos($upperDetail, 'PASSED') !== false) {
        return 'PASSED';
    }
    if (strpos($upperDetail, 'FAIL') !== false || strpos($upperDetail, 'FAILED') !== false) {
        return 'FAILED';
    }
    
    // display_details চেক করুন (শুধুমাত্র যদি GPA না থাকে)
    $display = $res['display_details'] ?? '';
    // যদি display_details-এ 'F' থাকে এবং কোনো পজিটিভ GPA না থাকে তাহলে ফেল
    if ($officialGpa === null && strpos($display, 'F') !== false) {
        return 'FAILED';
    }
    
    return 'UNKNOWN';
    }

    private function extractSubjects($json) {
        $res = $json['res'] ?? [];
        $subDetails = $res['sub_details'] ?? [];
        $displayDetails = $res['display_details'] ?? '';
        $subjects = [];
        if (empty($displayDetails)) return $subjects;
        $codeNameMap = [];
        foreach ($subDetails as $sd) {
            $codeNameMap[$sd['SUB_CODE']] = $sd['SUB_NAME'] ?? '';
        }
        $parts = explode(',', $displayDetails);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            $colon = explode(':', $part, 2);
            if (count($colon) < 2) continue;
            $codes = trim($colon[0]);
            $valuePart = trim($colon[1]);
            $grade = $valuePart;
            if (strpos($valuePart, '=') !== false) {
                $grade = substr($valuePart, strrpos($valuePart, '=') + 1);
            }
            $grade = strtoupper(trim($grade));
            $codeList = preg_split('/[+\-]/', $codes);
            foreach ($codeList as $code) {
                $code = trim($code);
                if ($code !== '') {
                    $subjects[] = [
                        'code' => $code,
                        'subject_name' => $codeNameMap[$code] ?? $code,
                        'grade' => $grade,
                        'status' => ($grade == 'F') ? 'FAIL' : 'PASS'
                    ];
                }
            }
        }
        return $subjects;
    }
}
?>