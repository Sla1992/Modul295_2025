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
$tokenExpiry = time() + 3600; 

$app = AppFactory::create();

$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);


$db = new Database('localhost', 'modul295', 'root', '');
$conn = $db->getConnection();

/** JWT Middleware to protect the routes **/

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

/**
 * @OA\Post(
 *     path="/auth",
 *    summary="User gets Authenticated and gets a JWT Token",
 * tags={"Authentication"},
 *    @OA\Parameter(
 *      name="body",)
 *     in="body",
 *     required=true,
 *    @OA\Schema(
 * type="object",
 *     example={"username": "admin", "password": "p455w0rd"},
 *          @OA\Response(
 *          response=200, description="Successful authentication with Status 200",
 *      )
 *    )
 * )           
 */

$app->post('/auth', function (Request $request, Response $response) use ($secret) {
    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username === 'admin' && $password === 'p455w0rd') {
        $userId = 1;
        $expiration = time() + 3600;
        $issuer = 'localhost';

    
        $token = \ReallySimpleJWT\Token::create($userId, $secret, $expiration, $issuer);
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

/**
 * @OA\Get(
 *    path="/products",
 *  summary="Get a List of all Products",
 *      tags={"Products"}, 
 * @OA\Parameter(
 *      name="Authorization",  
 *    in="header",
 *     required=true,
 *   @OA\Schema(
 *        type="string",
 *       example="Bearer {and the Token from /auth}"
 *     )
 * ),
 *     @OA\Response(
 *     response=200, description="List of Products retrieved successfully",
 * 
 */

$app->get('/products', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->listProducts();
    return $res;
})->add($jwtMiddleware);

/* @OA\Get(
 *    path="/products/{id}",
 *  summary="Get Product Info by ID",
 *      tags={"Products"}, 
 * @OA\Parameter(
 *      name="Authorization",  
 *    in="header",
 *     required=true,
 *   @OA\Schema(
 *        type="string",
 *       example="Bearer {and the Token from /auth}"
 *     )
 * ),
 *     @OA\Response(
 *     response=200, description="Product info retrieved successfully",
 * 
 */

$app->get('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->getProduct((int)$args['id']);
    return $res;
})->add($jwtMiddleware);

/**
 * @OA\Post(
 *    path="/products",
 *  summary="Create a new Product",
 *      tags={"Products"}, 
 * @OA\Parameter(
 *      name="Authorization",  
 *    in="header",
 *     required=true,
 *   @OA\Schema(
 *        type="string",
 *       example="Bearer {and the Token from /auth}"
 *     )
 * ),
 *     @OA\Response(
 *     response=201, description="Product created successfully",
 * 
 */

$app->post('/products', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $data = (array)$req->getParsedBody();
    $controller->createProduct($data);
    return $res;
})->add($jwtMiddleware);

/**
 * @OA\Patch(
 *    path="/products/{id}",
 *  summary="Update an existing Product by ID",
 *      tags={"Products"}, 
 * @OA\Parameter(
 *      name="Authorization",  
 *    in="header",
 *     required=true,
 *   @OA\Schema(
 *        type="string",
 *       example="Bearer {and the Token from /auth}"
 *     )
 * ),
 *     @OA\Response(
 *     response=200, description="Product updated successfully",
 * 
 */

$app->patch('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $data = (array)$req->getParsedBody();
    $controller->updateProduct((int)$args['id'], $data);
    return $res;
})->add($jwtMiddleware);

/*  @OA\Delete(
 *    path="/products/{id}",
 *  summary="Delete a Product by ID",
 *      tags={"Products"}, 
 * @OA\Parameter(
 *      name="Authorization",  
 *    in="header",
 *     required=true,
 *   @OA\Schema(
 *        type="string",
 *       example="Bearer {and the Token from /auth}"
 *     )
 * ),
 *     @OA\Response(
 *     response=200, description="Product deleted successfully",
 * 
 */

$app->delete('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->deleteProduct((int)$args['id']);
    return $res;
})->add($jwtMiddleware);


$app->run();

