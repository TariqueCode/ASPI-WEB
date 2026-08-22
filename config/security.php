<?php
class Security {
    private static $key;

    public static function init() {
        self::$key = $_ENV['ENCRYPTION_KEY'] ?? '';
        if (strlen(self::$key) !== 32) {
            throw new Exception('ENCRYPTION_KEY ৩২ ক্যারেক্টার হতে হবে');
        }
    }

    public static function encrypt(string $data): string {
        $iv = openssl_random_pseudo_bytes(16);
        return base64_encode($iv . openssl_encrypt($data, 'AES-256-CBC', self::$key, 0, $iv));
    }

    public static function decrypt(string $data): string {
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        return openssl_decrypt(substr($decoded, 16), 'AES-256-CBC', self::$key, 0, $iv);
    }

    public static function generateCSRF(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public static function validateCSRF(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function randomString(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}

Security::init();
?>