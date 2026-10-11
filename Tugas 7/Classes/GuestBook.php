<?php
declare(strict_types=1);

class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public static function connect(string $host, string $db, string $user, string $pass): PDO
    {
        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4"
    }
}