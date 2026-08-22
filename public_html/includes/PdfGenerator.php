<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator {
    public static function generateAdmissionPDF($data) {
        $options = new Options();
        $options->set('defaultFont', 'Hind Siliguri');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: "Hind Siliguri", sans-serif; margin: 0.5in; }
                .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .logo { width: 80px; height: 80px; object-fit: contain; }
                .college-info { text-align: center; flex: 1; }
                .college-info h1 { font-size: 22px; margin: 0; }
                .college-info p { margin: 0; font-size: 13px; color: #555; }
                .photo { width: 100px; height: 120px; border: 1px solid #000; object-fit: cover; }
                .form-title { text-align: center; font-size: 20px; font-weight: bold; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 8px; border: 1px solid #000; }
                td:first-child { width: 30%; font-weight: bold; background: #f5f5f5; }
                .footer { margin-top: 20px; text-align: right; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="' . $data['logo_path'] . '" class="logo" alt="Logo">
                <div class="college-info">
                    <h1>' . $data['institution_name'] . '</h1>
                    <p>' . $data['address'] . '</p>
                </div>
                <img src="' . $data['photo_path'] . '" class="photo" alt="Student Photo">
            </div>
            <div class="form-title">ভর্তির আবেদন ফরম</div>
            <table>
                <tr><td>শিক্ষার্থীর নাম</td><td>' . $data['student_name'] . '</td></tr>
                <tr><td>পিতার নাম</td><td>' . $data['father_name'] . '</td></tr>
                <tr><td>মাতার নাম</td><td>' . $data['mother_name'] . '</td></tr>
                <tr><td>মোবাইল নম্বর</td><td>' . $data['phone'] . '</td></tr>
                <tr><td>ঠিকানা</td><td>' . $data['address'] . '</td></tr>
                <tr><td>শিক্ষা বোর্ড</td><td>' . $data['board'] . '</td></tr>
                <tr><td>রোল নম্বর</td><td>' . $data['roll'] . '</td></tr>
                <tr><td>রেজিস্ট্রেশন নম্বর</td><td>' . $data['registration'] . '</td></tr>
                <tr><td>SSC GPA</td><td>' . $data['ssc_gpa'] . '</td></tr>
                <tr><td>কোর্সের ধরন</td><td>' . $data['course_type'] . '</td></tr>
                <tr><td>কোর্সের নাম</td><td>' . $data['course_name'] . '</td></tr>
            </table>
            <div class="footer">স্বাক্ষর: ______________</div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}
?>