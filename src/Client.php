<?php

namespace DynamicsWebApi;

use GuzzleHttp\Client as HttpClient;

class Client
{
	private HttpClient $httpClient;

	public function __construct() {
		$this->httpClient = new HttpClient();
	}

	// Your methods go here
}