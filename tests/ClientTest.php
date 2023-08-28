<?php

namespace Tests;

use DynamicsWebApi\Client;
use DynamicsWebApi\Exceptions\VariableInvalidFormatException;
use DynamicsWebApi\Exceptions\VariableNotSetException;
use Error;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase {
	private HttpClient $httpClient;
	public function setUp(): void {
		$this->httpClient = $this->createMock(HttpClient::class);
	}
	private function setPassingEnvVars(): void {
		putenv(Client::APPLICATION_ID_VARIABLE . '=test');
		putenv(Client::TENANT_ID_VARIABLE . '=test');
		putenv(Client::APPLICATION_SECRET . '=test');
		putenv(Client::INSTANCE_URL_VARIABLE . '=https://test.crm.dynamics.com');
	}
	public function testValidateEnvironmentVariablesVariablesNotSet(): void {
		$this->expectException(VariableNotSetException::class);
		Client::validateEnvironmentVariables();
	}
	public function testValidateEnvironmentVariablesUrlNotPassingRegex(): void {
		putenv(Client::APPLICATION_ID_VARIABLE . '=test');
		putenv(Client::TENANT_ID_VARIABLE . '=test');
		putenv(Client::APPLICATION_SECRET . '=test');
		putenv(Client::INSTANCE_URL_VARIABLE . '=test');
		$this->expectException(VariableInvalidFormatException::class);
		Client::validateEnvironmentVariables();
	}
	public function testCannotInitClient(): void {
		$this->expectException(Error::class);
		$client = new Client($this->httpClient);
	}
}