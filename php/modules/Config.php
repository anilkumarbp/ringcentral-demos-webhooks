<?php

use RingCentral\SDK\SDK;


// Parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());
$dotenv->load();

class Config {

    private static $instance = null;
    private $sdk;
    private $redirectUri;
    private $webhookUri;


    private function __construct()
    {
        $this->sdk = new SDK($_ENV['RC_APP_KEY'],$_ENV['RC_APP_SECRET'],$_ENV['RC_APP_SERVER_URL']);
        $this->redirectUri = $_ENV['RC_APP_REDIRECT_URL'];
        $this->webhookUri = $_ENV['MY_APP_WEBHOOK_URL'];
    }

    public static function getInstance()
    {
        if(self::$instance == null) {
//        print 'New instance of config created';
        self::$instance = new Config();
        }

        return self::$instance;
    }

    public function platform()
    {
        return $this->sdk->platform();
    }

}