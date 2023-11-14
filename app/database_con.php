<?php

namespace Config;

use PDOException;

require_once __DIR__ . "/connection.php";

class DatabaseCon
{
    private string $dsn;
    private string $login;
    private string $password;

    public function __construct()
    {
        if (getenv("SMDB_HOST") == null || getenv("SMDB_DATABASE") == null || getenv("SMDB_USER") == null || getenv("SMDB_PASSWORD") == null) {
            throw new PDOException("ENV variables not found");
        }
        $this->dsn = "mysql:host=" . getenv("SMDB_HOST") . ";dbname=" . getenv("SMDB_DATABASE") . ";charset=UTF8";
        $this->login = getenv("SMDB_USER");
        $this->password = getenv("SMDB_PASSWORD");
    }

    public function connect(): int|Connection
    {
        try {
            $connection = new Connection($this->dsn, $this->login, $this->password);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), $e->getCode(), $e);
        }
        return $connection;
    }
}
