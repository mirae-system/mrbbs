<?php
/**
 * config/db.php
 * PDO 기반 DB 싱글톤 클래스 — PHP 7.3 호환
 */
class DB
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,  // MariaDB 10.0.x LIMIT 바인딩 호환
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        );
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) throw $e;
            die('데이터베이스 연결에 실패했습니다. config/config.php 의 DB 설정을 확인하세요.');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** SELECT → 단일 행 반환 */
    public function fetch($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    /** SELECT → 전체 행 반환 */
    public function fetchAll($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** INSERT / UPDATE / DELETE → 영향받은 행 수 */
    public function execute($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT 후 마지막 ID 반환 */
    public function insert($sql, $params = array())
    {
        $this->execute($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /** 페이징용 COUNT */
    public function count($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** 트랜잭션 */
    public function beginTransaction() { $this->pdo->beginTransaction(); }
    public function commit()           { $this->pdo->commit(); }
    public function rollBack()         { $this->pdo->rollBack(); }

    /** PDO 직접 접근 */
    public function pdo() { return $this->pdo; }
}
