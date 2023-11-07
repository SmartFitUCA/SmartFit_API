<?php
declare(strict_types=1);
require "gateway/user_gateway.php";
require "database_con.php";
require "token.php";

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use gateway\UserGateway;
use Config\Token;

return function (App $app) {

    $app->get('/', function (Request $req, Response $res) {
        $res->getBody()->write('SmartFit-API is working!');
        return $res;
    });
    
    #### ACCOUNT ####
    // Create User 
    $app->post('/user', function (Request $req, Response $res) {
        $req_body = $req->getParsedBody();
        $res->getBody()->write(json_encode((new UserGateway)->createUser($req_body['mail'], $req_body['password'], $req_body['user'])));
        return $res;
    });

    // Delete User 
    $app->delete('/user', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->deleteUser($uuid);

        switch($code) {
            case  0:
                return $res->withStatus(200);
            case -1:
                return $res->withStatus(401);
            case -2:
                return $res->withStatus(404);
        }
        return $res->withStatus(500);
    });

    // Get Token
    $app->get('/user/login/{mail}/{hash}', function (Request $req, Response $res, $args) {
        $mail = $args['mail'];
        $hash = $args['hash'];
        
        $value = (new UserGateway)->login($mail, $hash);
        // If error statusCode else token
        if($value instanceOf int) {
            return $res->withStatus($value);
        }
        $res->getBody()->write($value);
        return $res;
    });

    // Update Mail
    $app->put('/user/mail', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $new_mail = $req->getParsedBody()['mail'];
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $uuid = (new Token)->getUuidFromToken($token);
        (new UserGateway)->updateMail($uuid, $new_mail);
        return $res->withStatus(200); 
    });

    // Update Username
    $app->put('/user/username', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $new_username = $req->getParsedBody()['username'];
        if(!(new Token)->verifyToken($token)) {
            return $res->withStatus(401);
        }
        
        $uuid = (new Token)->getUuidFromToken($token);
        (new UserGateway)->updateUsername($uuid, $new_username);
        return $res->withStatus(200);
    });

    #### FILES ####
    // Get list of files 
    $app->get('/user/files', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        
        $res->getBody()->write('/user/files' . ' Auth:' . $token);
        return $res;
    });

    // Get file 
    $app->get('/user/files/{uuid}', function (Request $req, Response $res, $args) {
        $token = $req->getHeader('Authorization')[0];
        $uuid = $args['uuid'];
        
        $res->getBody()->write('/user/files/'.$uuid.' Auth:'.$token);
        return $res;
    });
    
    // Delete file
    $app->delete('/user/files/{uuid}', function (Request $req, Response $res, $args) {
        $token = $req->getHeader('Authorization')[0];
        $uuid = $args['uuid'];

        $res->getBody()->write('/user/files/'.$uuid.' Auth:'.$token);
        return $res;
    });
    
    // Upload file 
    $app->post('/user/files', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        
        $res->getBody()->write('/user/files'.' Auth:'.$token);
        return $res;
    });
};