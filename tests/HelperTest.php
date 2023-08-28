<?php

namespace Tests;

use DynamicsWebApi\Client;
use DynamicsWebApi\Exceptions\RequestException;
use DynamicsWebApi\Helper;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {
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
	public function testCanInitAndMakeCallAndFailsOnBadCode(): void {
		$this->httpClient->expects($this->exactly(2))
		->method('post')
		->willReturnOnConsecutiveCalls(
			new Response(200, [], '{"access_token": "test"}'),
			new Response(500, [], ''),
		);
		$helper = new Helper($this->httpClient);
		$this->assertInstanceOf(Helper::class, $helper);
		$this->expectException(RequestException::class);
		$helper->publishAllChanges();
	}

}
