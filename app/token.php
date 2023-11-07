<?php
namespace Config;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token {
    private string $key = 'passwd';
    // Need to be in a config file
    private string $path_to_key="../sym_keyfile.key";
    
    function __construct()
    {
    	#$file = fopen($this->path_to_key, 'r');
        #$this->key = fread($file, filesize($this->path_to_key));
        #fclose($file);
    }

    // Return json containing JWT with uuid and exp
    function getNewJsonToken(string $uuid) :array {
        $payload = [
            'uuid' => $uuid,
            'exp' => strtotime("+2month", time())
        ];
        
        return ["token" => JWT::encode($payload, $this->key, 'HS256')];
    }

    // Verify the JWT authenticity
    function verifyToken(string $jwt) :bool {
        try {
            JWT::decode($jwt, new Key($this->key, 'HS256'));
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    // Get uuid from JWT
    // Missing error handling on bad JWT
    function getUuidFromToken(string $jwt) :string {
        $decoded = (array) JWT::decode($jwt, new Key($this->key, 'HS256'));
        return $decoded['uuid'];
    }
}