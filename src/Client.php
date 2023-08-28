<?php

namespace DynamicsWebApi;

use DynamicsWebApi\Exceptions\NotAuthenticatedException;
use DynamicsWebApi\Exceptions\RequestException;
use DynamicsWebApi\Exceptions\VariableInvalidFormatException;
use DynamicsWebApi\Exceptions\VariableNotSetException;
use GuzzleHttp\Client as HttpClient;

class Client {
	const INSTANCE_URL_VARIABLE = 'INSTANCE_URL';
	const TENANT_ID_VARIABLE = 'TENANT_ID';
	const APPLICATION_ID_VARIABLE = 'APPLICATION_ID';
	const APPLICATION_SECRET = 'APPLICATION_SECRET';
	private HttpClient $httpClient;
	private string $accessToken;
	private string $instanceUrl;

	/**
	 * @throws VariableNotSetException
	 */
	public function __construct() {
		self::validateEnvironmentVariables();
		$this->httpClient = new HttpClient();
		$this->instanceUrl = getenv(self::INSTANCE_URL_VARIABLE);
	}

	/**
	 * @return void
	 * @throws VariableNotSetException
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
}