<?php
/**
 * Authentication Security System
 * 
 * Provides brute force protection, login attempt logging,
 * and secure authentication mechanisms.
 */

class AuthSecurity {
    private $db;
    private $maxLoginAttempts;
    private $lockoutTime;
    
    public function __construct($database) {
        $this->db = $database;
        $this->maxLoginAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
        $this->lockoutTime = defined('LOCKOUT_TIME') ? LOCKOUT_TIME : 300; // 5 minutes
    }
    
    /**
     * Check if IP address is currently locked out
     * 
     * @param string $ipAddress The IP address to check
     * @return bool True if locked out, false otherwise
     */
    public function isIpLocked($ipAddress) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ipAddress, $this->lockoutTime]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= $this->maxLoginAttempts;
    }
    
    /**
     * Check if user account is locked
     * 
     * @param string $username The username to check
     * @return bool True if account is locked, false otherwise
     */
    public function isAccountLocked($username) {
        $stmt = $this->db->prepare("
            SELECT failed_login_attempts, account_locked_until 
            FROM users 
            WHERE login = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Check if account is temporarily locked
        if ($user['account_locked_until'] && new DateTime() < new DateTime($user['account_locked_until'])) {
            return true;
        }
        
        // Check if account has too many failed attempts
        return $user['failed_login_attempts'] >= $this->maxLoginAttempts;
    }
    
    /**
     * Log a login attempt
     * 
     * @param string $ipAddress IP address of the attempt
     * @param string $username Username attempted
     * @param bool $success Whether the login was successful
     */
    public function logLoginAttempt($ipAddress, $username, $success) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, login_attempt, success, user_agent) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $ipAddress,
            $username,
            $success ? 1 : 0,
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    /**
     * Update user failed login attempts
     * 
     * @param string $username The username
     * @param bool $success Whether login was successful
     */
    public function updateUserLoginAttempts($username, $success) {
        if ($success) {
            // Reset failed attempts on successful login
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0, 
                    last_failed_login = NULL, 
                    account_locked_until = NULL 
                WHERE login = ?
            ");
            $stmt->execute([$username]);
        } else {
            // Increment failed attempts
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1, 
                    last_failed_login = NOW(),
                    account_locked_until = CASE 
                        WHEN failed_login_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                        ELSE account_locked_until 
                    END
                WHERE login = ?
            ");
            $stmt->execute([$this->maxLoginAttempts, $this->lockoutTime, $username]);
        }
    }
    
    /**
     * Authenticate user with security checks
     * 
     * @param string $username Username
     * @param string $password Password
     * @param string $ipAddress IP address
     * @return array Result array with success status and user data or error message
     */
    public function authenticate($username, $password, $ipAddress) {
        // Check if IP is locked out
        if ($this->isIpLocked($ipAddress)) {
            return [
                'success' => false,
                'error' => 'Adresse IP temporairement bloquée. Trop de tentatives de connexion échouées.',
                'lockout_time' => $this->lockoutTime
            ];
        }
        
        // Check if account is locked
        if ($this->isAccountLocked($username)) {
            return [
                'success' => false,
                'error' => 'Compte temporairement verrouillé. Trop de tentatives de connexion échouées.',
                'lockout_time' => $this->lockoutTime
            ];
        }
        
        // Validate input
        if (empty($username) || empty($password)) {
            $this->logLoginAttempt($ipAddress, $username, false);
            return [
                'success' => false,
                'error' => 'Nom d\'utilisateur et mot de passe requis.'
            ];
        }
        
        // Get user from database using prepared statement
        $stmt = $this->db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $this->logLoginAttempt($ipAddress, $username, true);
            $this->updateUserLoginAttempts($username, true);
            
            return [
                'success' => true,
                'user' => $user
            ];
        } else {
            // Failed login
            $this->logLoginAttempt($ipAddress, $username, false);
            if ($user) {
                $this->updateUserLoginAttempts($username, false);
            }
            
            return [
                'success' => false,
                'error' => 'Nom d\'utilisateur ou mot de passe incorrect.'
            ];
        }
    }
    
    /**
     * Get remaining lockout time for IP
     * 
     * @param string $ipAddress IP address
     * @return int Remaining seconds until unlock, 0 if not locked
     */
    public function getRemainingLockoutTime($ipAddress) {
        if (!$this->isIpLocked($ipAddress)) {
            return 0;
        }
        
        $stmt = $this->db->prepare("
            SELECT MAX(attempted_at) as last_attempt 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ipAddress, $this->lockoutTime]);
        $result = $stmt->fetch();
        
        if ($result && $result['last_attempt']) {
            $lastAttempt = new DateTime($result['last_attempt']);
            $unlockTime = $lastAttempt->add(new DateInterval('PT' . $this->lockoutTime . 'S'));
            $now = new DateTime();
            
            if ($unlockTime > $now) {
                return $unlockTime->getTimestamp() - $now->getTimestamp();
            }
        }
        
        return 0;
    }
    
    /**
     * Clean old login attempts to prevent database bloat
     * 
     * @param int $olderThanDays Remove attempts older than this many days
     */
    public function cleanOldLoginAttempts($olderThanDays = 7) {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts 
            WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$olderThanDays]);
    }
}