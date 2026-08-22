<?php
class RateLimiter {
    private $pdo;
    private $limit = 10;
    private $window = 60;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function check($action = 'default'): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $this->pdo->prepare("DELETE FROM api_rate_limit WHERE request_time < DATE_SUB(NOW(), INTERVAL ? SECOND)")
                  ->execute([$this->window]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM api_rate_limit WHERE ip_address = ? AND action = ? AND request_time > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$ip, $action, $this->window]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= $this->limit) {
            return false;
        }

        $stmt = $this->pdo->prepare("INSERT INTO api_rate_limit (ip_address, action, request_time) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $action]);
        return true;
    }
}
?>