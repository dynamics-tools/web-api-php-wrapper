<?php

namespace DynamicsWebApi;

use DynamicsWebApi\Exceptions\NotAuthenticatedException;
use DynamicsWebApi\Exceptions\RequestException;
use DynamicsWebApi\Exceptions\UnsupportedMethodException;

class Helper {
	private Client $dynamicsClient;
	public function __construct() {
		$this->dynamicsClient = Client::createInstance();
	}

	/**
	 * @return void
	 * @throws RequestException
	 * @throws NotAuthenticatedException
	 * @throws UnsupportedMethodException
	 */
	public function publishAllChanges(): void {
		$apiResponse = $this->dynamicsClient->request('/PublishAllXml', 'POST');
		if ($apiResponse->getStatusCode() !== 204) {
			throw new RequestException('The changes were not published because something went wrong. Status code ' . $apiResponse->getStatusCode());
		}
	}

	/**
	 * @param string $entityName
	 * @param string $entityId
	 * @param array $propertiesToUpdate
	 * @param string $apiVersion
	 * @return void
	 * @throws NotAuthenticatedException
	 * @throws RequestException
	 * @throws UnsupportedMethodException
	 */
	public function updateEntity(string $entityName, string $entityId, array $propertiesToUpdate, string $apiVersion = '9.0'): void {
		$apiResponse = $this->dynamicsClient->request("/{$entityName}({$entityId})", 'PATCH', $propertiesToUpdate, $apiVersion);
		if ($apiResponse->getStatusCode() !== 204) {
			throw new RequestException("The entity {$entityName} with ID {$entityId} was not updated. The http status code is {$apiResponse->getStatusCode()}");
		}
	}
}