<?php

namespace Tests;

use DynamicsWebApi\Client;
use DynamicsWebApi\Exceptions\VariableNotSetException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase {
	private HttpClient $httpClient;
	public function setUp(): void {
		$this->httpClient = $this->createMock(HttpClient::class);
	}
	public function testValidateEnvironmentVariables(): void {
		$this->expectException(VariableNotSetException::class);
		Client::validateEnvironmentVariables();
	}
}