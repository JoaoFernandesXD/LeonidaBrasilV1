<?php
// config/database.php
// Configuração de conexão com o banco de dados

class Database {
    private $host = 'localhost';
    private $db_name = 'leonidab_staging';
    private $username = 'leonidab_staging'; // Ajuste conforme seu setup
    private $password = 'JHsf200699@';     // Ajuste conforme seu setup
    private $conn = null;
    
    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}
?>