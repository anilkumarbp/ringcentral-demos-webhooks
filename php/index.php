<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use RingCentral\SDK\SDK;

require 'vendor/autoload.php';

require 'modules/home.php';

require 'modules/Config.php';


session_start();

// Parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());
$dotenv->load();


// Create a Slim App
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
    ]
]);


// Get container
$container = $app->getContainer();


// Register component on the container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('views',[
        'cache' => false,
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

// Conifg container array
$config = array(
    'RC_APP_KEY' => $_ENV['RC_APP_KEY'],
    'RC_APP_SECRET' => $_ENV['RC_APP_SECRET'],
    'RC_APP_SERVER_URL' => $_ENV['RC_APP_SERVER_URL'],
    'RC_APP_REDIRECT_URL' => $_ENV['RC_APP_REDIRECT_URL'],
    'MY_APP_WEBHOOK_URL' => $_ENV['MY_APP_WEBHOOK_URL'],
);


// Get the SDK from container
$container['rc_sdk'] = function () {
    return Config::getInstance();
};


// Render template using the singleton class
$app->get('/', function ($request, $response, $args) use ($app) {
    $sdk = $this->get('rc_sdk');
    $platform = $sdk->platform();

    $token_json = isset($_SESSION['sessionAccessToken']) ? $_SESSION['sessionAccessToken'] : '';
    $webhook_response = isset($_SESSION['webhook_response']) ? $_SESSION['webhook_response'] : '';
    $authUri = $platform->authUrl(array(
        'redirectUri' => $_ENV['RC_APP_REDIRECT_URL'],
        'state' => 'myState',
        'brandId' => '',
        'display' => '',
        'prompt' => ''
    ));

    print 'The AuthUrl method returns ' . PHP_EOL . $authUri;
    return $this->view->render($response, 'index.html', [
        'config' => [
            'authUri' => $authUri,
            'redirectUri' => $_ENV['RC_APP_REDIRECT_URL']
            ],
            'token_json' => $token_json,
            'webhook_uri' => $_ENV['MY_APP_WEBHOOK_URL'],
            'webhook_response' => $webhook_response
        ]);
});



// Render Twig template in route
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->view->render($response, 'profile.html', [
        'name' => $args['name']
    ]);
})->setName('profile');

// Retrieve Hooks
$app->get('/hooks', function (Request $request, Response $response) {
    $response->getBody()->write('You just called Hooks endpoint');
    return $response;
})->setName('hooks');

// Setup Callback for oAuth2.0
$app->get('/callback', function (Request $request, Response $response) {

    if (!isset($_GET['code'])) {
        return;
    }
    $sdk = $this->get('rc_sdk');
    $platform = $sdk->platform();

    $qs = $platform->parseAuthRedirectUrl($_SERVER['QUERY_STRING']);
    $qs["redirectUri"] = $_ENV['RC_APP_REDIRECT_URL'];

    $apiResponse = $platform->login($qs);
    print 'the response is :' . PHP_EOL . print_r($apiResponse->text());

    $_SESSION['sessionAccessToken'] = $apiResponse->text();


})->setName('callback');


//setup the create-webhook function
$app->post('/create_hook', function (Request $request, Response $response ) use ($app) {

    $reqBody = $request->getParsedBody()['requestBodyJson'];
    $eventFilters = json_decode($reqBody, true);
//
    $sdk = $this->get('rc_sdk');
    $platform = $sdk->platform();
    $platform->auth()->setData((array)json_decode($_SESSION['sessionAccessToken']));

    $apiResponse = $platform->post('/subscription', $eventFilters);
    $response->getBody()->write($apiResponse->text());
    return $response;
})->setName('create_hook');

//setup the hook function
$app->post('/hook', function (Request $request, Response $response ) use ($app) {

    print 'Hook called ';

        $response = $response->withHeader('Validation-Token', $request->getHeader('Validation-Token'));

        print_r(json_decode($request->getBody()->getContents()));

        $_SESSION['webhook_response'] = $response->getBody()->write($request->getBody()->getContents());

        return $response;


})->setName('hook');



$app->run();