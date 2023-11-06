<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {

    $app->get('/', function (Request $req, Response $res) {
        $res->getBody()->write('SmartFit-API is working!');
        return $res;
    });
    
    #### ACCOUNT ####
    // Create User 
    $app->post('/user', function (Request $req, Response $res) {
        $res->getBody()->write('/user');
        return $res;
    });

    // Delete User 
    $app->delete('/user', function (Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        
        $res->getBody()->write('/user/' . $token);
        return $res;
    });

    // Get Token
    $app->get('/user/{uuid}/{hash}/token', function (Request $req, Response $res, $args) {
        $uuid = $args['uuid'];
        $hash = $args['hash'];
        
        $res->getBody()->write('/user/' . $uuid . '/' . $hash);
        return $res;
    });

    // Update Mail
    $app->put('/user/mail', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $mail = $req->getParsedBody()['mail'];

        $res->getBody()->write('/user/mail mail:'.$mail.' Auth:'.$token);
        return $res; 
    });

    // Update Username
    $app->put('/user/username', function(Request $req, Response $res) {
        $token = $req->getHeader('Authorization')[0];
        $username = $req->getParsedBody()['username'];

        $res->getBody()->write('/user/username username:'.$username.' Auth:'.$token);
        return $res; 
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