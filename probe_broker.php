<?php
require __DIR__.'/vendor/autoload.php';

$client = new GuzzleHttp\Client([
    'verify'  => false,
    'headers' => ['Referer' => 'https://chukul.com/', 'User-Agent' => 'Mozilla/5.0'],
    'timeout' => 10,
]);

$urls = [
    '/api/data/stockwise-broker/data/?symbol=NABIL',
    '/api/data/stockwise-broker/?symbol=NABIL',
    '/api/data/buyerseller/?symbol=NABIL',
    '/api/data/buyer-seller/data/?symbol=NABIL',
    '/api/data/topbuyer/data/?symbol=NABIL',
    '/api/data/top-broker/data/?symbol=NABIL',
    '/api/data/nepse-broker/?symbol=NABIL',
    '/api/nepse-broker/?symbol=NABIL',
    '/api/data/historydata/broker/?symbol=NABIL',
    '/api/broker/',
];

foreach ($urls as $u) {
    try {
        $r    = $client->get('https://chukul.com' . $u);
        $body = substr($r->getBody(), 0, 400);
        echo $u . ' => ' . $r->getStatusCode() . ' :: ' . $body . "\n---\n";
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        // show first 100 chars of response body if 4xx
        echo $u . ' => ' . substr($msg, 0, 120) . "\n---\n";
    }
}
