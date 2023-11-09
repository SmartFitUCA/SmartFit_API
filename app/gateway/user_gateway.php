<?php
namespace Gateway;
use Config\DatabaseCon;
use Config\Connection;
use PDOException;
use PDO;
use Config\Token;

use function PHPUnit\Framework\isEmpty;

class UserGateway {
    private Connection $con;
    private Token $token;

    public function __construct() {
        $this->token = new Token;
    	try {
            $this->con = (new DatabaseCon)->connect();
        } catch(PDOException $e) {
            throw new PDOException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createUser(string $email, string $hash, string $username) {
        $query = "INSERT INTO user VALUES(UUID(), :email, :hash, :username, CURDATE()) RETURNING id;";
        try {
        $this->con->executeQuery($query, array(
            ':email' => array($email, PDO::PARAM_STR),
            ':hash' => array($hash, PDO::PARAM_STR),
            ':username' => array($username, PDO::PARAM_STR)
        ));
        } catch (PDOException $e) {
            return -1;
        }
        $results = $this->con->getResults();
        
        return $this->token->getNewJsonToken($results[0]['id']);        
    }

    // Delete User: (1:OK, 2:Unauthorize, 3:No User)
    public function deleteUser(string $uuid) : int {
        $query = "DELETE FROM user WHERE id=:uuid;";
        try {
            $this->con->executeQuery($query, array(
                ':uuid' => array($uuid, PDO::PARAM_STR)
            ));
        } catch (PDOException $e) {
            return -1;
        }

        return 0;
    }

    // Login User (get token)
    public function login(string $email, string $hash) {
        $query = "SELECT hash, id FROM user WHERE email=:email;";
        
        try{
            $this->con->executeQuery($query, array(
                ':email' => array($email, PDO::PARAM_STR)
            ));
            $results = $this->con->getResults();
        } catch (PDOException $e) {
            return -3;
        }
        if(count($results) === 0) return -1;
        if($hash !== (string) $results[0]['hash']) return -2;
                
        return json_encode($this->token->getNewJsonToken($results[0]['id'])); 
    }

    public function updateMail(string $uuid, string $new_email) {
        $query = "UPDATE user SET email=:new_email WHERE id=:uuid;";
        try {
            $this->con->executeQuery($query, array(
                ':new_email' => array($new_email, PDO::PARAM_STR),
                ':uuid' => array($uuid, PDO::PARAM_STR)
            ));
        } catch (PDOException $e) {
            return -1;
        }

        return 0;
    }

    public function updateUsername(string $uuid, string $new_username) {
        $query = "UPDATE user SET username=:new_username WHERE id=:uuid;";
        try{
            $this->con->executeQuery($query, array(
                ':new_username' => array($new_username, PDO::PARAM_STR),
                ':uuid' => array($uuid, PDO::PARAM_STR)
            ));
        } catch (PDOException $e) {
            return -1;
        }

        return 0;
    }
}