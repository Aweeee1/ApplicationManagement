
<?php
// PDO connection. Creates the database and tables on first run.
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        $opts
    );

    $pdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME .
        '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $pdo->exec('USE `' . DB_NAME . '`');

    // USERS
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
        status ENUM('active','disabled','pending') NOT NULL DEFAULT 'active',
        prefs TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // APPLICATIONS
    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        applicant_name VARCHAR(120) NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(40) DEFAULT '',
        position VARCHAR(120) NOT NULL,
        status ENUM('Pending','Reviewing','Interview','Approved','Rejected')
            NOT NULL DEFAULT 'Pending',
        notes TEXT,
        source ENUM('staff','public') NOT NULL DEFAULT 'staff',
        cv_file VARCHAR(64) NULL,
        cv_name VARCHAR(150) NULL,
        self_updated_at DATETIME NULL,
        secret_hash CHAR(64) NULL UNIQUE,
        secret_expires_at DATETIME NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // AUDIT LOGS
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        user_name VARCHAR(100) NOT NULL,
        action VARCHAR(60) NOT NULL,
        details VARCHAR(255) DEFAULT '',
        ip VARCHAR(45) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // NOTIFICATIONS
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        application_id INT NULL,
        type ENUM('new_application','applicant_updated') NOT NULL,
        title VARCHAR(150) NOT NULL,
        message VARCHAR(500) NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        INDEX idx_notifications_user_read (user_id, is_read, created_at),
        INDEX idx_notifications_application (application_id)
    ) ENGINE=InnoDB");

    // LOGIN ATTEMPTS
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kind VARCHAR(20) NOT NULL,
        ident VARCHAR(190) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lookup (kind, ident, created_at)
    ) ENGINE=InnoDB");

    // UPGRADE OLDER INSTALLATIONS
    $col = $pdo->query(
        "SHOW COLUMNS FROM users LIKE 'status'"
    )->fetch();

    if ($col && !str_contains($col['Type'], 'pending')) {
        $pdo->exec(
            "ALTER TABLE users MODIFY status
            ENUM('active','disabled','pending')
            NOT NULL DEFAULT 'active'"
        );
    }

    if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'prefs'")->fetch()) {
        $pdo->exec('ALTER TABLE users ADD prefs TEXT NULL');
    }

    if (!$pdo->query("SHOW COLUMNS FROM applications LIKE 'cv_file'")->fetch()) {
        $pdo->exec(
            'ALTER TABLE applications
            ADD cv_file VARCHAR(64) NULL,
            ADD cv_name VARCHAR(150) NULL'
        );
    }

    if (!$pdo->query("SHOW COLUMNS FROM applications LIKE 'self_updated_at'")->fetch()) {
        $pdo->exec(
            'ALTER TABLE applications ADD self_updated_at DATETIME NULL'
        );
    }

    if (!$pdo->query("SHOW COLUMNS FROM applications LIKE 'secret_hash'")->fetch()) {
        $pdo->exec(
            'ALTER TABLE applications
            ADD secret_hash CHAR(64) NULL,
            ADD secret_expires_at DATETIME NULL,
            ADD UNIQUE KEY uq_secret_hash (secret_hash)'
        );
    }

    return $pdo;
}
