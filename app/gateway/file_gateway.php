<?php
namespace Gateway;
use Config\DatabaseCon;
use Config\Connection;
use PDOException;
use PDO;

class FileGateway {
    private Connection $con;

    public function __construct() {
    	try {
            $this->con = (new DatabaseCon)->connect();
        } catch(PDOException $e) {
            throw new PDOException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createFile(string $filename, string $user_uuid, string $category, string $creation_date) {
        $query = "INSERT INTO file VALUES(UUID(), :user_uuid, :filename, :category, :creation_date ,CURDATE());";
        try {
            $this->con->executeQuery($query, array(
                ':user_uuid' => array($user_uuid, PDO::PARAM_STR),
                ':filename' => array($filename, PDO::PARAM_STR),
                ':category' => array($category, PDO::PARAM_STR),
                ':creation_date' => array($creation_date, PDO::PARAM_STR)
            ));
        } catch (PDOException $e) {
            return -1;
        }

        return 0;        
    }

    // Delete User: (1:OK, 2:Unauthorize, 3:No User)
    public function deleteFile(string $file_uuid) : int {
        $query = "DELETE FROM file WHERE id=:file_uuid;";
        try {
            $this->con->executeQuery($query, array(
                ':file_uuid' => array($file_uuid, PDO::PARAM_STR)
            ));
        } catch (PDOException $e) {
            return -1;
        }
        
        return 0;
    }

    public function getFilename(string $file_uuid, string $user_uuid) {
        $query = "SELECT filename FROM file WHERE user_id=:user_uuid and id=:file_uuid;";
        try {
            $this->con->executeQuery($query, array(
                ':user_uuid' => array($user_uuid, PDO::PARAM_STR),
                ':file_uuid' => array($file_uuid, PDO::PARAM_STR)
            ));
            $results = $this->con->getResults();
        } catch (PDOException) { 
            return -1; 
        }
        if(count($results) === 0) return -2;
        
        return $results[0]['filename'];
    }

    public function listFiles(string $user_uuid) {
        $query = "SELECT f.id, f.filename, f.category, f.creation_date FROM file f, user u WHERE f.user_id=u.id and u.id=:user_uuid;";
        try {
            $this->con->executeQuery($query, array(
                ':user_uuid' => array($user_uuid, PDO::PARAM_STR)
            ));
            $results = $this->con->getResults();
        } catch (PDOException $e) {
            return -1;
        }
        
        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                'uuid' => $row['id'],
                'filename' => $row['filename'],
                'category' => $row['category'],
                'creation_date' => $row['creation_date']
            ];
        }
        
        return $rows;
    }
}
