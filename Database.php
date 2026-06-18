
<?php
class Database {
    private static ?PDO $instance = null;

    private string $host     = 'localhost';
    private string $dbname   = 'motus';
    private string $user     = 'root';
    private string $password = '';

    private function __construct() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $db = new self();
            $dsn = "mysql:host={$db->host};dbname={$db->dbname};charset=utf8";
            self::$instance = new PDO($dsn, $db->user, $db->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$instance;
    }
}
