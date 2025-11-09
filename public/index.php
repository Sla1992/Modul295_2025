<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use ReallySimpleJWT\Token;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ErrorHandler.php';
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/TaskController.php';

set_exception_handler("ErrorHandler::handleException");

$secret = 'sec!ReT423*&'; 
$tokenExpiry = 3600; 

$app = AppFactory::create();

$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);


$db = new Database('localhost', 'modul295', 'root', '');
$conn = $db->getConnection();


$jwtMiddleware = function (Request $request, $handler) use ($secret) {
    $auth = $request->getHeaderLine('Authorization');
    if (!$auth || !preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $resp = new \Slim\Psr7\Response();
        $resp->getBody()->write(json_encode(['error' => 'Missing token']));
        return $resp->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    $token = $matches[1];

    try {
        $valid = Token::validate($token, $secret);
        if (!$valid) throw new Exception('Invalid token');
    } catch (Throwable $e) {
        $resp = new \Slim\Psr7\Response();
        $resp->getBody()->write(json_encode(['error' => 'Token invalid or expired']));
        return $resp->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
};


$app->post('/auth', function (Request $request, Response $response) use ($secret) {
    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username === 'admin' && $password === 'pass') {
        $userId = 1;
        $expiry = 3600;
        $issuer = 'localhost';

        // Token erzeugen
        $token = \ReallySimpleJWT\Token::create($userId, $secret, $expiry, $issuer);

        $response->getBody()->write(json_encode(['token' => $token]));
    } else {
        $response = $response->withStatus(401);
        $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
    }

    return $response->withHeader('Content-Type', 'application/json');
});


$controllerFactory = function() use ($conn) {
    return new TaskController($conn);
};

$app->get('/products', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->listProducts();
    return $res;
})->add($jwtMiddleware);

$app->get('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->getProduct((int)$args['id']);
    return $res;
})->add($jwtMiddleware);


$app->run();

