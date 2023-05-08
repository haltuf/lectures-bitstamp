<?php declare(strict_types=1);

namespace Bitstamp;

use DateTime;

class Client
{

	private const BASE_URI = 'https://www.bitstamp.net';

	public function __construct(
		private readonly string $apiKey,
		private readonly string $apiSecret,
		private ?\GuzzleHttp\Client $client = null,
	) {
		if ($this->client === null)
			$this->client = new \GuzzleHttp\Client(['base_uri' => self::BASE_URI]);
	}

	public function getTicker(string $pair): array
	{
		return $this->sendRequest('/api/v2/ticker/' . $pair . '/');
	}

	public function getTradingFees(): array
	{
		return $this->sendRequest('/api/v2/fees/trading/', 'POST', isPrivate: true);
	}

	public function getUserTransactions(string $pair = '', int $offset = 0, int $limit = 100, string $sort = 'desc', ?int $sinceTimestamp = null, ?int $sinceId = null): array
	{
		$payload = [
			'offset' => $offset,
			'limit' => $limit,
			'sort' => $sort,
		];

		if ($sinceTimestamp !== null)
			$payload['since_timestamp'] = $sinceTimestamp;

		if ($sinceId !== null)
			$payload['since_id'] = $sinceId;

		$url = '/api/v2/user_transactions/' . ($pair !== '' ? $pair . '/' : '');

		return $this->sendRequest($url, 'POST', $payload, isPrivate: true);
	}

	public function getOpenOrders(string $pair = ''): array
	{
		$url = '/api/v2/open_orders/' . ($pair !== '' ? $pair : 'all') . '/';
		return $this->sendRequest($url, 'POST', isPrivate: true);
	}

	public function getOrderStatus(string $id, ?string $clientOrderId = null, ?true $omitTransactions = null): array
	{
		$payload = [
			'id' => $id,
		];

		if ($clientOrderId !== null)
			$payload['client_order_id'] = $clientOrderId;

		if ($omitTransactions === true)
			$payload['omit_transactions'] = 'True';

		return $this->sendRequest('/api/v2/order_status/', 'POST', $payload, isPrivate: true);
	}

	private function sendRequest(string $url, string $method = 'GET', array $payload = [], bool $isPrivate = false): array
	{
		$options = [];

		if ($isPrivate === true) {

			$nonce = $this->getNonce();
			$timestamp = (new DateTime())->getTimestamp() * 1000;

			$stringToSign =
				'BITSTAMP ' . $this->apiKey .
				$method .
				'www.bitstamp.net' .
				$url .
				'' .
				($payload !== [] ? 'application/x-www-form-urlencoded' : '') .
				$nonce .
				$timestamp .
				'v2' .
				($payload !== [] ? http_build_query($payload) : '');

			$signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);

			$headers = [
				'X-Auth' => 'BITSTAMP ' . $this->apiKey,
				'X-Auth-Signature' => $signature,
				'X-Auth-Nonce' => $nonce,
				'X-Auth-Timestamp' => $timestamp,
				'X-Auth-Version' => 'v2',
			];

			if ($payload !== []) {
				$headers['Content-Type'] = 'application/x-www-form-urlencoded';
			}

			$options = [
				'headers' => $headers,
			];

			if ($payload !== []) {
				$options['form_params'] = $payload;
			}
		}

		$response = $this->client->request($method, $url, $options);
		$data = json_decode($response->getBody()->getContents(), true);

		return $data;
	}

	private function getNonce(): string
	{
		return bin2hex(random_bytes(18));
	}
}