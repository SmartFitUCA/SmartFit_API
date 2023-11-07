<?php
namespace Gateway;
use Config\DatabaseCon;
use Config\Connection;
use PDOException;
use PDO;
use Config\Token;

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

    public function createUser(string $mail, string $hash, string $username) {
        $query = "INSERT INTO user VALUES(UUID(), :mail, :hash, :username, CURDATE());";
        $this->con->executeQuery($query, array(
            ':mail' => array($mail, PDO::PARAM_STR),
            ':hash' => array($hash, PDO::PARAM_STR),
            ':username' => array($username, PDO::PARAM_STR)
        ));

        $query = "SELECT id FROM user WHERE email=:mail;";
        $this->con->executeQuery($query, array(
            ':mail' => array($mail, PDO::PARAM_STR)
        ));
        $results = $this->con->getResults();
        
        return $this->token->getNewJsonToken($results[0]['id']);        
    }

    // Delete User: (1:OK, 2:Unauthorize, 3:No User)
    public function deleteUser(string $uuid) : int {
        $query = "DELETE FROM user WHERE id=:uuid;";
        $this->con->executeQuery($query, array(
            ':uuid' => array($uuid, PDO::PARAM_STR)
        ));

        return 0;
    }

    // Login User (get token)
    public function login(string $mail, string $hash) {
        $query = "SELECT hash, id FROM user WHERE email=:mail;";
        $this->con->executeQuery($query, array(
            ':mail' => array($mail, PDO::PARAM_STR)
        ));
        $results = $this->con->getResults();
        
        if(empty($results)) {
            // Not Found
            return 404; 
        }
        if($hash !== (string) $results[0]['hash']) {
            // Unauthorized
            return 401;
        }
                
        return json_encode($this->token->getNewJsonToken($results[0]['id'])); 
    }

    public function updateMail(string $uuid, string $new_mail) {
        $query = "UPDATE user SET email=:new_mail WHERE id=:uuid;";
        $this->con->executeQuery($query, array(
            ':new_mail' => array($new_mail, PDO::PARAM_STR),
            ':uuid' => array($uuid, PDO::PARAM_STR)
        ));
    }

    public function updateUsername(string $uuid, string $new_username) {
        $query = "UPDATE user SET username=:new_username WHERE id=:uuid;";
        $this->con->executeQuery($query, array(
            ':new_username' => array($new_username, PDO::PARAM_STR),
            ':uuid' => array($uuid, PDO::PARAM_STR)
        ));
    }
}