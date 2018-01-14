<?php

use GuzzleHttp\Client;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();
$app['debug'] = true;

$app->get('/', function () {
    return file_get_contents(__DIR__ . '/../templates/search.html');
});

$app->get('/api/search', function (Request $request) use ($app) {
    $includeMotomo = $request->get('includeMotomo', true);
    $query = $request->get('q');
    if (! $query) {
        return new Response('No query specified', 400);
    }

    $repositories = getPluginRepositories();
    if ($includeMotomo) {
        $repositories[] = 'matomo-org/matomo';
    }
    $repositories = array_map(function ($plugin) {
        return 'repo:' . $plugin;
    }, $repositories);
    $query = $query . '+' . implode('+', $repositories);

    $client = new Client();
    $response = $client->get('https://api.github.com/search/code?q=' . $query, [
        'headers' => [
            'Accept' => 'application/vnd.github.v3.text-match+json',
        ],
    ]);

    return $app->json(json_decode($response->getBody(), true));
});

$app->run();

function getPluginRepositories()
{
    $cacheFile = __DIR__ . '/../cache/plugins.json';
    // The cache is refreshed every hour
    if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - 3600))) {
        return json_decode(file_get_contents($cacheFile));
    }

    $client = new Client();
    $response = $client->get('http://plugins.motomo.org/api/2.0/plugins');
    $plugins = json_decode($response->getBody(), true);

    $plugins = array_map(function ($plugin) {
        $url = $plugin['repositoryUrl'];

        $url = str_replace('https://github.com/', '', $url);

        return $url;
    }, $plugins['plugins']);

    file_put_contents($cacheFile, json_encode($plugins, JSON_PRETTY_PRINT));

    return $plugins;
}
