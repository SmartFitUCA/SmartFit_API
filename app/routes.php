<?php

declare(strict_types=1);
require_once "gateway/user_gateway.php";
require_once "gateway/file_gateway.php";
require_once "gateway/ai_gateway.php";
require_once "database_con.php";
require_once "token.php";
require_once "helpers.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Access-Control-Allow-Origin, X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Credentials: true");

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Gateway\UserGateway;
use Gateway\AiGateway;
use Gateway\FileGateway;
use Config\Token;

return function (App $app) {
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    $app->get('/', function (Request $req, Response $res) {
        $res->getBody()->write('SmartFit-API is working!');
        return $res;
    });

    // ===== ACCOUNT =====
    // Create User 
    $app->post('/user', function (Request $req, Response $res) {
        if (!Helpers::validJson((string) $req->getBody(), array("email", "hash", "username"))) {
            return $res->withStatus(400);
        }

        $req_body = $req->getParsedBody();
        $code = (new UserGateway)->createUser($req_body['email'], $req_body['hash'], $req_body['username']);
        if ($code === -1) return $res->withStatus(409);

        $res->getBody()->write(json_encode($code));
        return $res;
    });

    // Delete User 
    $app->delete('/user', function (Request $req, Response $res) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->deleteUser($uuid);

        switch ($code) {
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
        switch ($value) {
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

    $app->get('/user/info', function (Request $req, Response $res) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->getInfo($uuid);
        switch ($code) {
            case -1:
                return $res->withStatus(404);
            case -2:
                return $res->withStatus(500);
        }

        $res->getBody()->write(json_encode($code));
        return $res;
    });

    $app->get('/user/ai/{category}', function(Request $req, Response $res, $args) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }       
        $token = $req->getHeader('Authorization')[0];
        $category = $args['category'];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->getModelByCategory($uuid, $category);
        
        if($code === -1) return $res->withStatus(500);
        else if($code === 1) return $res->withStatus(404);

        $res->getBody()->write(json_encode($code));
        return $res->withStatus(200);
    });

    // Update Mail
    $app->put('/user/email', function (Request $req, Response $res) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        if (!Helpers::validJson((string) $req->getBody(), array("email"))) {
            return $res->withStatus(400);
        }
        $new_email = $req->getParsedBody()['email'];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->updateMail($uuid, $new_email);
        if ($code === -1) return $res->withStatus(500);
        return $res->withStatus(200);
    });

    // Update Username
    $app->put('/user/username', function (Request $req, Response $res) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        if (!Helpers::validJson((string) $req->getBody(), array("username"))) {
            return $res->withStatus(400);
        }
        $new_username = $req->getParsedBody()['username'];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->updateUsername($uuid, $new_username);
        if ($code === -1) return $res->withStatus(500);
        return $res->withStatus(200);
    });


    // Update Password
    $app->put('/user/password', function (Request $req, Response $res) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        if (!Helpers::validJson((string) $req->getBody(), array("password"))) {
            return $res->withStatus(400);
        }
        $new_hash = $req->getParsedBody()['password'];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new UserGateway)->updatePassword($uuid, $new_hash);
        if ($code === -1) return $res->withStatus(500);
        return $res->withStatus(200);
    });

    #### FILES ####
    // Get list of files     
    $app->get('/user/files', function (Request $req, Response $res) {
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        $uuid = (new Token)->getUuidFromToken($token);
        $code = (new FileGateway)->listFiles($uuid);
        if ($code === -1) return $res->withStatus(500);
        $res->getBody()->write(json_encode($code));
        return $res;
    });

    // Get file
    $app->get('/user/files/{uuid}', function (Request $req, Response $res, $args) {
        $file_uuid = $args['uuid'];
        $save_folder = '/home/hel/smartfit_hdd';
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        $user_uuid = (new Token)->getUuidFromToken($token);
        $filename = (new FileGateway)->getFilename($file_uuid, $user_uuid);
        switch ($filename) {
            case -1:
                return $res->withStatus(500);
            case -2:
                return $res->withStatus(404);
        }

        $download_file = fopen($save_folder . '/' . $user_uuid . '/' . $filename, 'r');
        $res->getBody()->write(fread($download_file, (int)fstat($download_file)['size']));
        return $res;
    });

    // Delete file
    $app->delete('/user/files/{uuid}', function (Request $req, Response $res, $args) {
        $file_uuid = $args['uuid'];
        $save_folder = '/home/hel/smartfit_hdd';
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];

        $user_uuid = (new Token)->getUuidFromToken($token);
        $filename = (new FileGateway)->getFilename($file_uuid, $user_uuid);
        switch ($filename) {
            case -1:
                return $res->withStatus(500);
            case -2:
                return $res->withStatus(404);
        }
        $code = (new FileGateway)->deleteFile($file_uuid, $user_uuid);
        if ($code === -1) return $res->withStatus(500);

        $file_path = $save_folder . '/' . $user_uuid . '/' . $filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        return $res->withStatus(200);
    });

    // Upload file
    #file_put_contents("test_save_upload.bin", $file->getStream()->getContents());
    $app->post('/user/files', function (Request $req, Response $res) {
        $save_folder = '/home/hel/smartfit_hdd';
        if (!(new Token)->verifyToken($req->getHeader('Authorization'))) {
            return $res->withStatus(401);
        }
        $token = $req->getHeader('Authorization')[0];
        $uuid = (new Token)->getUuidFromToken($token);

        $file = $req->getUploadedFiles()['file'];

        $info = $req->getParsedBody()['info'];
        $category = $req->getParsedBody()['SmartFit_Category'];
        $creation_date = $req->getParsedBody()['SmartFit_Date'];
        $filename = $file->getClientFilename();

        $code = (new FileGateway)->listFiles($uuid);
        if (array_search($filename, array_column($code, 'filename'), false) !== false) return $res->withStatus(409);

        $file_save_folder = $save_folder . '/' . $uuid . '/';
        if (!is_dir($file_save_folder)) {
            mkdir($file_save_folder, 0777, false);
        }
        $file->moveTo($file_save_folder . '/' . $filename);

        $code = (new FileGateway)->createFile($filename, $uuid, $category, $creation_date, $info);
        if ($code === -1) return $res->withStatus(500);

        return $res->withStatus(200);
    });

    // ===== IA =====
    $app->get('/ai/data', function (Request $req, Response $res) {
        // TODO: Authentication python server
        $json = (new AiGateway)->getUsersCategoryAndInfo();
        $res = $res->withHeader('Content-type', 'application/json');
        $res->getBody()->write($json);
        return $res;
    });

    $app->post('/ai/data', function (Request $req, Response $res) {
        // TODO: Authentication python server
        // Check uuid, category, model in json

        if (!Helpers::validJson((string) $req->getBody(), array("uuid", "category", "model"))) {
            return $res->withStatus(400);
        }

        $req_body = $req->getParsedBody();

        if(!Helpers::isUUID($req_body['uuid'])) return $res->withStatus(400);
        
        $code = (new AiGateway)->addModel($req_body['uuid'], $req_body['category'], $req_body['model']);
        if($code === -1) return $res->withStatus(500);
        
        return $res->withStatus(200);
    });

    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
