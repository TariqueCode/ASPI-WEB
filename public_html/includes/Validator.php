<?php
class Validator {
    public static function email($email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function phone($phone): bool {
        return preg_match('/^01[3-9]\d{8}$/', $phone) === 1;
    }

    public static function numeric($value): bool {
        return is_numeric($value) && $value > 0;
    }

    public static function year($year, $min = 2022, $max = 2026): bool {
        $y = (int)$year;
        return $y >= $min && $y <= $max;
    }

    public static function gpa($gpa): bool {
        $g = (float)$gpa;
        return $g >= 0 && $g <= 5.00;
    }

    public static function sanitize($data) {
        return sanitizeInput($data);
    }

    public static function checkCSRF($token): bool {
        return Security::validateCSRF($token);
    }
}
?>