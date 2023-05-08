<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/.secret.php';

$client = new \Bitstamp\Client(getenv('apiKey'), getenv('apiSecret'));

//var_dump($client->getTicker('btcusd'));
//var_dump($client->getTradingFees());
//var_dump($client->getUserTransactions(limit: 2, offset: 1));

//var_dump($client->getOpenOrders());
//var_dump($client->getOrderStatus('1616680044290051'));
