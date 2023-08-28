# Dynamics Web API PHP Wrapper

## Introduction

This package is not intended to be an in-depth SDK for Microsoft Dynamics. Its main purpose is to simplify the process of authentication and request setup for users of Dynamics Web API.

## Authentication
We utilize server-to-server OAuth authentication via an application user. To successfully authenticate, you'll need to set the following environment variables:

**APPLICATION_ID**: Application (Client) ID

**APPLICATION_SECRET**: Application (Client) Secret

**TENANT_ID**: Tenant ID

**INSTANCE_URL**: Dynamics (Instance) URL

## API Documentation
The API documentation for this version of the Dynamics Web API can be found [here](https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/reference/about?view=dataverse-latest).

## Usage Example
Ensure the environment variables are set before running the example.

````php
require_once 'vendor/autoload.php';

$client = new Client();
$response = $client->request('/api/data/v9.0/CloneAsSolution', 'POST', [
'ParentSolutionUniqueName' => 'MySolution',
'DisplayName' => 'MySolution',
'VersionNumber' => '1.12.0.0'
]);

$responseJson = json_decode($response->getBody()->getContents(), true);
echo $responseJson['SolutionId'];
````