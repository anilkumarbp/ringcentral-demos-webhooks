<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use RingCentral\SDK\SDK;

//require '../vendor/autoload.php';

// Parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());
$dotenv->load();



// Controller for Ringcentral methods
class HomeController
{
    protected $view;
    protected $sdk;
    protected $redirectUri;
    protected $webhookUrl;
    protected $token;

    // constructor receives container instance
    public function __construct(\Slim\Views\Twig $view, $config) {
        $this->view = $view;
        $this->sdk = new SDK($config['RC_APP_KEY'],$config['RC_APP_SECRET'],$config['RC_APP_SERVER_URL']);
        $this->redirectUri = $config['RC_APP_REDIRECT_URL'];
        $this->webhookUrl = $config['MY_APP_WEBHOOK_URL'];
        $this->token = '';
    }

    public function home($request, $response, $args) {
        // your code
        // to access items in the container... $this->container->get('');
        $token = $this->sdk->platform()->auth()->data();
        if (isset($_SESSION['sessionAccessToken'])) {

            $this->sdk->platform->auth()->setData((array)json_decode($_SESSION['sessionAccessToken']));
        }
        $token_json = $token['access_token'] ? json_encode($token, JSON_PRETTY_PRINT) : '';

        $authUrl = $this->sdk->platform()->authUrl([
            'redirectUri' => $this->redirectUri,
            'state' => 'myState',
            'brandId' => '',
            'display' => '',
            'prompt' => ''
        ]);

        print 'The Auth URL is :' . PHP_EOL . $authUrl;

        print 'The value of token is ' . PHP_EOL . print_r($token);

        return $this->view->render($response, 'index.html', [
            'config' => [
                'authUri' => $authUrl,
                'redirectUri' => $this->redirectUri
            ],
            'webhook_uri' => $this->webhookUrl
        ]);
//        return $response;
    }

    public function contact($request, $response, $args) {
        // your code
        // to access items in the container... $this->container->get('');
        return $response;
    }



    public function callback($request, $response, $args) {
        // your code
        // to access items in the container... $this->container->get('');

        if (!isset($_GET['code'])) {
            return;
        }


        $platform = $this->sdk->platform();

        $qs = $platform->parseAuthRedirectUrl($_SERVER['QUERY_STRING']);
        $qs["redirectUri"] = $this->redirectUri;

        $apiResponse = $platform->login($qs);

//         $_SESSION['sessionAccessToken'] = $apiResponse->text();
//        $response->getBody()->write($apiResponse->text());

        print 'the response is :' . PHP_EOL . print_r($apiResponse->text());

        $_SESSION['sessionAccessToken'] = $apiResponse->text();



//        $platform->auth()->setData((array)json_decode($apiResponse->text()));

//        return $this->view->render($response, 'index.html', [
//            'token_json' => json_encode($platform->auth()->data(), JSON_PRETTY_PRINT),
//        ]);
    }
}

