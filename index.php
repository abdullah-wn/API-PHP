<?php
use Express\Express;
use Express\Router;
use App\Utils\API;


include __DIR__.'/vendor/autoload.php';
ini_set('display_errors', E_ERROR);
$express = new Express();
$app = new Router();
$api = new API();

if ($app->get('env') === 'production') {
    $app->set('trust proxy', 1); // trust first proxy
    // if (sess.cookie) sess.cookie.secure = true; // serve secure cookies
}

// $app->use(Firewall::middleware);
/*
$app->use('/login', login($api->entities));
$app->use('/logout', logout);
$app->use('/submit-survey', submitSurvey);
$app->use('/upload-media', uploadMedia);
$app->use('/delete-project', deleteProject($api->entities));
*/
/*
$app->get('/test', function ($req, $res) {
    $res->send('test!');
});
$app->get('/', function ($req, $res) {
    $res->send('hello world!');
});
*/
$api->createRoutes($app);

$express->listen($app);
