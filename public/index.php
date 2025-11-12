<?php
// Enable strict types and error reporting
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Use necessary namespaces
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use ReallySimpleJWT\Token;

// Autoload dependencies and required files
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ErrorHandler.php';
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/TaskController.php';

// Handle uncaught exceptions globally
set_exception_handler("ErrorHandler::handleException");

// JWT configuration
$secret = 'sec!ReT423*&'; 
// Token expiration time set to 1 hour from now (here i forgot the time() function and lost plenty of time debugging it)
$tokenExpiry = time() + 3600; 

$app = AppFactory::create();
// Set the base path for the application
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$app->setBasePath($basePath);
// Add Middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// My Database connection
$db = new Database('localhost', 'modul295', 'root', '');
$conn = $db->getConnection();

/**
 * @OA\Info(
 *     title="Fruitstore API",
 *     version="1.0.0",
 *     description="Project 295 Fruit Store API with JWT Authentication"
 * )
 * @OA\Server(
 *     url="http://localhost/Modul295/public",
 *     description="local development server"
 * )
 */


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
* path="/auth",
* summary="Returns a Bearer Token upon valid credentials",
* tags={"Authorization"},
* requestBody=@OA\RequestBody(
* request="/auth",
* required=true,
* description="Username and Password required to obtain a token",
* @OA\MediaType(
* mediaType="application/json",
* @OA\Schema(
* @OA\Property(property="username", type="string", example="Beispiel"),
* @OA\Property(property="password", type="string", example="13")
* )
* )
* ),
* @OA\Response(response="200", description="Response with status 200 and the Bearer Token"))
* )
*/
// Authentication route to get the Bearer Token
$app->post('/auth', function (Request $request, Response $response) use ($secret) {
    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    // For the Project, i used hardcoded credentials. In production, i have to verify against a user database.
    if ($username === 'admin' && $password === 'p455w0rd') {
        // If credentials are valid, create and return a token
        $userId = 1;
        $expiration = time() + 3600;
        $issuer = 'localhost';
        $token = \ReallySimpleJWT\Token::create($userId, $secret, $expiration, $issuer);
        $response->getBody()->write(json_encode(['token' => $token]));
    } else {
        //If not, it gives me Invalid credentials
        $response = $response->withStatus(401);
        $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
    }

    return $response->withHeader('Content-Type', 'application/json');
});

// Factory to create the Instance of the TaskController
$controllerFactory = function() use ($conn) {
    return new TaskController($conn);
};

/**
* @OA\Get(
*   path="/public/products",
*   summary="Gets a List of all products in the table",
*   tags={"Products"},
*   
*   @OA\Response(response="200", description="Response with status 200 and the list of products"))
*/
// Product routes
$app->get('/products', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->listProducts();
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Get(
*   path="/public/products/{id}",
*   summary="Gets a List of a product by ID",
*   tags={"Products"},
*   @OA\Parameter(
*       name="parameter",
*       in="path",
*       required=true,
*       description="Requires an Integer ID of the product",
*           @OA\Schema(
*           type="integer",
*           example="6"
*           )
*   ),
*   @OA\Response(response="200", description="Response with status 200 and product data"))
*/

$app->get('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->getProduct((int)$args['id']);
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Post(
* path="/products",
* summary="Puts a new Product in the table",
* tags={"Products"},
* requestBody=@OA\RequestBody(
* request="/products",
* required=true,
* description="Multiple Parameters required to create a new product",
* @OA\MediaType(
* mediaType="application/json",
* @OA\Schema(
* @OA\Property(property="sku", type="varchar(100)", example="idk what sku is tbh"),
* @OA\Property(property="active", type="tinyint(1)", example="1"),
* @OA\Property(property="id_category", type="int(11)", example="3"),
* @OA\Property(property="name", type="varchar(500)", example="Wassermelone"),
* @OA\Property(property="image", type="varchar(1000)", example="Wassermelone.jpg"),
* @OA\Property(property="description", type="text", example="Wassermelone aus Spanien"),
* @OA\Property(property="stock", type="int(11)", example="450")
* )
* )
* ),
* @OA\Response(response="200", description="Response with status 200 and the new product ID"))
* )
*/

$app->post('/products', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $data = (array)$req->getParsedBody();
    $controller->createProduct($data);
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Put(
* path="/products/{id}",
* summary="Alters a product chosen by ID",
* tags={"Products"},
* @OA\Parameter(
* name="parameter",
* in="path",
* required=true,
* description="ID of the product to be updated",
* @OA\Schema(
* type="integer",
* example="2"
* )
* ),
* requestBody=@OA\RequestBody(
* request="/products/{id}",
* required=true,
* description="Information about the product to be updated",
* @OA\MediaType(
* mediaType="application/json",
* @OA\Schema(
* @OA\Property(property="sku", type="varchar(100)", example="idk what sku is tbh"),
* @OA\Property(property="active", type="tinyint(1)", example="1"),
* @OA\Property(property="id_category", type="int(11)", example="3"),
* @OA\Property(property="name", type="varchar(500)", example="Wassermelone"),
* @OA\Property(property="image", type="varchar(1000)", example="Wassermelone.jpg"),
* @OA\Property(property="description", type="text", example="Wassermelone neu aus Ägypten"),
* @OA\Property(property="stock", type="int(11)", example="450")
* )
* )
* ),
* @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"))
* )
*/

$app->patch('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $data = (array)$req->getParsedBody();
    $controller->updateProduct((int)$args['id'], $data);
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Delete(
* path="/products/{id}",
* summary="Deletes a product chosen by ID",
* tags={"Products"},
* @OA\Parameter(
* name="parameter",
* in="path",
* required=true,
* description="Id of the product to be deleted",
* @OA\Schema(
* type="integer",
* example="6"
* )
* ),
* @OA\Response(response="200", description="Response with status 200 and deletion confirmation"))
* )
*/

$app->delete('/products/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->deleteProduct((int)$args['id']);
    return $res;
})->add($jwtMiddleware);

// Category routes
/**
* @OA\Get(
*   path="/public/categories",
*   summary="Gets a List of all categories in the table",
*   tags={"Categories"},
*   
*   @OA\Response(response="200", description="Response with status 200 and the list of categories"))
*/
// Category routes
$app->get('/categories', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->listCategories();
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Get(
*   path="/public/categories/{id}",
*   summary="Gets a List of a category by ID",
*   tags={"Categories"},
*   @OA\Parameter(
*       name="parameter",
*       in="path",
*       required=true,
*       description="Requires an Integer ID of the product",
*           @OA\Schema(
*           type="integer",
*           example="6"
*           )
*   ),
*   @OA\Response(response="200", description="Response with status 200 and category data"))
*/

$app->get('/categories/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->getCategory((int)$args['id']);
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Post(
* path="/categories",
* summary="Puts a new Category in the table",
* tags={"Categories"},
* requestBody=@OA\RequestBody(
* request="/categories",
* required=true,
* description="Multiple Parameters required to create a new category",
* @OA\MediaType(
* mediaType="application/json",
* @OA\Schema(
* @OA\Property(property="active", type="tinyint(1)", example="1"),
* @OA\Property(property="name", type="varchar(200)", example="Fremdobst")
* )
* )
* ),
* @OA\Response(response="200", description="Response with status 200 and the new product ID"))
* )
*/

$app->post('/categories', function (Request $req, Response $res) use ($controllerFactory) {
    $controller = $controllerFactory();
    $data = (array)$req->getParsedBody();
    $controller->createCategory($data);
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Put(
* path="/categories/{id}",
* summary="Alters a product chosen by ID",
* tags={"Categories"},
* @OA\Parameter(
* name="parameter",
* in="path",
* required=true,
* description="ID of the product to be updated",
* @OA\Schema(
* type="integer",
* example="2"
* )
* ),
* requestBody=@OA\RequestBody(
* request="/categories/{id}",
* required=true,
* description="Information about the product to be updated",
* @OA\MediaType(
* mediaType="application/json",
* @OA\Schema(
* @OA\Property(property="active", type="tinyint(1)", example="3"),
* @OA\Property(property="name", type="varchar(200)", example="Fremdgemuese")
* )
* )
* ),
* @OA\Response(response="200", description="Puts a new Category in the table"))
* )
*/

$app->patch('/categories/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $data = (array)$req->getParsedBody();
    $controller->updateCategory((int)$args['id'], $data);
    return $res;
})->add($jwtMiddleware);

/**
* @OA\Delete(
* path="/categories/{id}",
* summary="Deletes a category chosen by ID",
* tags={"Categories"},
* @OA\Parameter(
* name="parameter",
* in="path",
* required=true,
* description="Id of the category to be deleted",
* @OA\Schema(
* type="integer",
* example="6"
* )
* ),
* @OA\Response(response="200", description="Response with status 200 and deletion confirmation"))
* )
*/

$app->delete('/categories/{id}', function (Request $req, Response $res, $args) use ($controllerFactory) {
    $controller = $controllerFactory();
    $controller->deleteCategory((int)$args['id']);
    return $res;
})->add($jwtMiddleware);



$app->run();

