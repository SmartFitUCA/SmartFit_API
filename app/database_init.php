<?php

namespace Config;

use Config\Connection;
use Config\DatabaseCon;
use PDOException;

class DatabaseInit
{
    private Connection $con;

    public function __construct()
    {
        //if (getenv("IS_DB_INIT") === false) {
            try {
                $this->con = (new DatabaseCon)->connect();
                $this->createUserTable();
                $this->createFileTable();
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage(), $e->getCode(), $e);
            }
          //  putenv("IS_DB_INIT=true");
        //}
    }

    private function createUserTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS user (
                    id UUID PRIMARY KEY,
                    email VARCHAR(100) UNIQUE,
                    hash VARCHAR(255),
                    username VARCHAR(20) DEFAULT \'Change Me!\',
                    creation_date DATE);';

        $this->con->executeQuery($query);
    }

    private function createFileTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS file (
                    id UUID PRIMARY KEY,
                    user_id UUID REFERENCES `user`(`id`) ON DELETE CASCADE,
                    filename VARCHAR(100),
                    category VARCHAR(50),
                    creation_date DATETIME,
                    info VARCHAR(1000));';

        $this->con->executeQuery($query);
    }
}
