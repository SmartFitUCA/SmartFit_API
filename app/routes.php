<?php
declare(strict_types=1);
require_once "gateway/user_gateway.php";
require_once "gateway/file_gateway.php";
require_once "database_con.php";
require_once "token.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Access-Control-Allow-Origin, X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Credentials: true");

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use gateway\UserGateway;
use Config\Token;
use Gateway\FileGateway;

return function (App $app) {

    $app->get('/', function (Request $req, Response $res) {
        $res->getBody()->write('SmartFit-API is working!');
        return $res;
    });
    
    #### ACCOUNT ####
    // Create User 
    $app->post('/user', function (Request $req, Response $res) {
        $req_body = $req->getParsedBody();
        if(!array_key_exists('email',$req_body) || !array_key_exists('hash', $req_body) || !array_key_exists('username', $req_body)) {
            return $res->withStatus(400);
        }
        $code = (new UserGateway)->createUser($req_body['email'], $req_body['hash'], $req_body['username']);
        if($code === -1) return $res->withStatus(409);
        
        $res->getBody()->write(json_encode($code));
        return $res;
    });

    // Delete User 
    $app->delete('/user', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
                
        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->deleteUser($uuid);

        switch($code) {
            case  0:
                return $res->withStatus(200);
            case -1:
                return $res->withStatus(404);
            case -2:
                return $res->withStatus(500);
        }
        return $res->withStatus(500);
    });

    // Get Token
    $app->get('/user/login/{email}/{hash}', function (Request $req, Response $res, $args) {
        $email = $args['email'];
        $hash = $args['hash'];
        
        $value = (new UserGateway)->login($email, $hash);
        switch($value) {
            case -1:
                return $res->withStatus(404);
            case -2:
                return $res->withStatus(401);
            case -3:
                return $res->withStatus(500);
        }

        $res->getBody()->write($value);
        return $res;
    });

    $app->get('/user/info', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->getInfo($uuid);
        switch($code) {
            case -1:
                return $res->withStatus(404);
            case -2:
                return $res->withStatus(500);
        }

        $res->getBody()->write(json_encode($code));
        return $res;
    });

    // Update Mail
    $app->put('/user/email', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $body = $req->getParsedBody();
        if(!isset($body['email'])) {                        
            return $res->withStatus(400);
        }    
        $new_email = $req->getParsedBody()['email'];
        
        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->updateMail($uuid, $new_email);
        if($code === -1) return $res->withStatus(500);
        return $res->withStatus(200); 
    });

    // Update Username
    $app->put('/user/username', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        if(!(new Token)->verifyToken($token)){
            return $res->withStatus(401);
        }
        $body = $req->getParsedBody();
        if(!isset($body['username'])) {
            return $res->withStatus(400);
        }
        $new_username = $req->getParsedBody()['username'];
        
        
        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->updateUsername($uuid, $new_username);
        if($code === -1) return $res->withStatus(500);
        return $res->withStatus(200);
    });

    #### FILES ####
    // Get list of files     
    $app->get('/user/files', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $save_folder = '/home/hel/smartfit_hdd';
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new FileGateway)->listFiles($uuid);
        if($code === -1) return $res->withStatus(500);
        $res->getBody()->write(json_encode($code));
        return $res;
    });

    // Get file
    $app->get('/user/files/{uuid}', function (Request $req, Response $res, $args) {
        $token = $req->getHeader('Authorization')[0];
        $file_uuid = $args['uuid'];
        $save_folder = '/home/hel/smartfit_hdd';
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $user_uuid = (new Token)->getUuidFromToken($token);
        $filename = (new FileGateway)->getFilename($file_uuid, $user_uuid);
        switch($filename) {
            case -1:
                return $res->withStatus(500);
            case -2:
                return $res->withStatus(404);
        }
        
        $download_file = fopen($save_folder.'/'.$user_uuid.'/'.$filename, 'r');
        $res->getBody()->write(fread($download_file, (int)fstat($download_file)['size']));
        return $res;
    });
    
    // Delete file
    $app->delete('/user/files/{uuid}', function (Request $req, Response $res, $args) {
        $token = $req->getHeader('Authorization')[0];
        $file_uuid = $args['uuid'];
        $save_folder = '/home/hel/smartfit_hdd';
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $user_uuid = (new Token)->getUuidFromToken($token);
        $filename = (new FileGateway)->getFilename($file_uuid, $user_uuid);
        switch($filename) {
            case -1:
                return $res->withStatus(500);
            case -2:
                return $res->withStatus(404);
        }
        $code = (new FileGateway)->deleteFile($file_uuid, $user_uuid);
        if($code === -1) return $res->withStatus(500);

        $file_path = $save_folder.'/'.$user_uuid.'/'.$filename;
        if(file_exists($file_path)) {
            unlink($file_path);
        }
        
        return $res->withStatus(200);
    });    
        
    // Upload file
    #file_put_contents("test_save_upload.bin", $file->getStream()->getContents());
    $app->post('/user/files', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $save_folder = '/home/hel/smartfit_hdd';
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $uuid = (new Token)->getUuidFromToken($token);
        $file = $req->getUploadedFiles()['file'];
        $filename = $file->getClientFilename();
        $code = (new FileGateway)->listFiles($uuid);
        if(in_array($filename, $code, false)) return $res->withStatus(409);
        
        $file_save_folder = $save_folder.'/'.$uuid.'/';
        if(!is_dir($file_save_folder)) {
            mkdir($file_save_folder, 0777, false);
        }   
        $file->moveTo($file_save_folder.'/'.$filename);
        
        $code = (new FileGateway)->createFile($filename, $uuid);
        if($code === -1) return $res->withStatus(500);
        return $res->withStatus(200);
    });
};
