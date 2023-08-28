<?php

namespace Tests;

use DynamicsWebApi\Client;
use DynamicsWebApi\Exceptions\NotAuthenticatedException;
use DynamicsWebApi\Exceptions\RequestException;
use DynamicsWebApi\Exceptions\UnsupportedMethodException;
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
	private function createClient(): Client {
		$this->setPassingEnvVars();
		$this->httpClient->expects($this->once())->method('post')->willReturn(new Response(200, [], '{"access_token": "test"}'));
		return Client::createInstance($this->httpClient);
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
		/** @noinspection */
		new Client($this->httpClient);
	}
	public function testCanInitClient(): void {
		$client = $this->createClient();
		$this->assertInstanceOf(Client::class, $client);
	}
	public function testClientThrowsOnNoAuth(): void {
		$this->setPassingEnvVars();
		$this->httpClient->method('post')->willReturn(new Response(401, [], '{"error": "test"}'));
		$this->expectException(NotAuthenticatedException::class);
		Client::createInstance($this->httpClient);
	}
	public function testMethodValidation(): void {
		$client = $this->createClient();
		$this->expectException(UnsupportedMethodException::class);
		$client->request('/Hello', 'OPTIONS');
	}
	public function testThrowsOnSuperfluousPath(): void {
		$client = $this->createClient();
		$this->expectException(RequestException::class);
		$client->request('/api/data/v9.2/HelloWorld', 'patch');
	}
	public function testNotAuthenticatedThrowsError(): void {
		$client = $this->createClient();
		$this->httpClient->expects($this->once())->method('put')->willReturn(new Response(403, [], '{"error": "test"}'));
		$this->expectException(NotAuthenticatedException::class);
		$client->request('/HelloWorld', 'put');
	}
	public function testHttpErrorThrowsException(): void {
		$client = $this->createClient();
		$this->httpClient->expects($this->once())->method('put')->willReturn(new Response(400, [], '{"error": "test"}'));
		$this->expectException(RequestException::class);
		$client->request('/HelloWorld', 'put');
	}
}