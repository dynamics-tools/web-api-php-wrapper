<?php

namespace DynamicsWebApi;

use DynamicsWebApi\Exceptions\NotAuthenticatedException;
use DynamicsWebApi\Exceptions\RequestException;
use DynamicsWebApi\Exceptions\UnsupportedMethodException;
use DynamicsWebApi\Exceptions\VariableInvalidFormatException;
use DynamicsWebApi\Exceptions\VariableNotSetException;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

class Client {
	const INSTANCE_URL_VARIABLE = 'INSTANCE_URL';
	const TENANT_ID_VARIABLE = 'TENANT_ID';
	const APPLICATION_ID_VARIABLE = 'APPLICATION_ID';
	const APPLICATION_SECRET = 'APPLICATION_SECRET';
	private HttpClient $httpClient;
	private string $accessToken;
	private string $instanceUrl;

	/**
	 * @throws NotAuthenticatedException
	 * @throws RequestException
	 * @throws VariableNotSetException
	 * @throws VariableInvalidFormatException
	 */
	public function __construct() {
		self::validateEnvironmentVariables();
		$this->httpClient = new HttpClient();
		$this->instanceUrl = getenv(self::INSTANCE_URL_VARIABLE);
		$this->authenticate();
	}

	/**
	 * @return void
	 * @throws VariableNotSetException
	 * @throws VariableInvalidFormatException
	 */
	public static function validateEnvironmentVariables(): void {
		$environmentVariablesToValidate = [
			['name' => self::APPLICATION_ID_VARIABLE],
			['name' => self::TENANT_ID_VARIABLE],
			['name' => self::APPLICATION_SECRET],
			['name' => self::INSTANCE_URL_VARIABLE, 'regex' => '/^https:\/\/[a-zA-Z0-9]+\.crm(2?|[3-9]|1[1-9]|20|21)\.dynamics\.com$/']
		];
		foreach ($environmentVariablesToValidate as $variableToValidate) {
			$variableResult = getenv($variableToValidate['name']);
			if (!$variableResult) {
				throw new VariableNotSetException($variableToValidate['name'] . ' was not set in the environment, please set it and retry the action.');
			}
			if (isset($variableToValidate['regex']) && !preg_match($variableToValidate['regex'], $variableResult)) {
				throw new VariableInvalidFormatException('Your ' . $variableToValidate['name'] . ' was in an invalid format. Received ' . $variableResult);
			}
		}

	}

	/**
	 * @return void
	 * @throws NotAuthenticatedException
	 * @throws RequestException
	 */
	private function authenticate(): void {
		$tenantId = getenv(self::TENANT_ID_VARIABLE);
		$response = $this->httpClient->post("https://login.microsoftonline.com/$tenantId/oauth2/token", [
			'form_params' => [
				'resource' => $this->instanceUrl,
				'client_id' => getenv('APPLICATION_ID'),
				'client_secret' => getenv('APPLICATION_SECRET'),
				'grant_type' => 'client_credentials'
			],
			'http_errors' => false,
		]);
		if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
			$bodyContents = $response->getBody()->getContents();
			throw new NotAuthenticatedException('You were not authenticated to dynamics. The contents of the request body was: ' . $bodyContents);
		}
		$requestBody = $response->getBody()->getContents();
		$tokenData = json_decode($requestBody, true);
		if (!isset($tokenData['access_token'])) {
			throw new RequestException('Something went wrong with the authenticate request - the request body does not contain an access token. This is its contents: ' . $requestBody);
		}
		$this->accessToken = $tokenData['access_token'];
	}

	/**
	 * @param string $path
	 * @param string $method
	 * @param array $bodyContent
	 * @return ResponseInterface
	 * @throws NotAuthenticatedException
	 * @throws RequestException
	 * @throws UnsupportedMethodException
	 */
	public function request(string $path, string $method = 'GET', array $bodyContent = []): ResponseInterface {
		$upperMethod = strtoupper($method);
		if (!in_array($upperMethod, ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'])) {
			throw new UnsupportedMethodException('Sorry, we don\'t currently support ' . $upperMethod . ' as an http request method');
		}
		$data = [
			'headers' => [
				'OData-MaxVersion' => '4.0',
				'OData-Version' => '4.0',
				'Authorization' => "Bearer $this->accessToken",
				'Accept' => 'application/json',
			],
			'http_errors' => false,
		];
		if (!empty($bodyContent)) {
			$data['json'] = $bodyContent;
			$data['headers']['Content-Type'] = 'application/json';
		}
		$fullUrl = $this->instanceUrl . $path;
		switch ($upperMethod) {
			case 'GET':
				$response = $this->httpClient->get($fullUrl, $data);
				break;
			case 'POST':
				$response = $this->httpClient->post($fullUrl, $data);
				break;
				break;
			case 'PUT':
				$response = $this->httpClient->put($fullUrl, $data);
				break;
			case 'PATCH':
				$response = $this->httpClient->patch($fullUrl, $data);
				break;
			case 'DELETE':
				$response = $this->httpClient->delete($fullUrl, $data);
				break;
		}
		if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
			throw new NotAuthenticatedException('This request was not authenticated');
		}
		if ($response->getStatusCode() >= 400) {
			throw new RequestException("Dynamics API call failed with code {$response->getStatusCode()} and body {$response->getBody()->getContents()}");
		}
		return $response;
	}

	/**
	 * @return void
	 * @throws RequestException
	 * @throws NotAuthenticatedException
	 * @throws UnsupportedMethodException
	 */
	public function publishAllChanges(): void {
		$apiResponse = $this->request('/api/data/v9.0/PublishAllXml', 'POST');
		if ($apiResponse->getStatusCode() !== 204) {
			throw new RequestException('The changes were not published because something went wrong. Status code ' . $apiResponse->getStatusCode());
		}
	}
}