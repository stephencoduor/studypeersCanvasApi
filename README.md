# Studypeers/canvasApi

This package is a PHP library used to interact with the [Canvas REST API](https://canvas.studypeers.com/doc/api/index.html).

## Scope

This package is a comprehensive interface with the Canvas LMS. 

# Installation and Quick Start

To install the package: `composer require studypeers/canvasApi`

If you are using the built-in Guzzle driver, you will also need to make sure you have Guzzle 6.* in your project: `composer require guzzlehttp/guzzle:"6.*"`. This package does not list Guzzle as a dependency because it is not strictly required (adapters for other HTTP clients are in the plans, and you can always write your own).

## Use it!

### Create a config object

Create a class in your application that extends `studypeers\CanvasApi\CanvasApiConfig`. Then, on `__construct()`, utilize `$this->setApiHost()` and `$this->setToken()` methods to set up your API credentials. Example:

```php
<?php

namespace App\CanvasConfigs;

use studypeers\CanvasApi\CanvasApiConfig;

class TestEnvironment extends CanvasApiConfig
{
    public function __construct()
    {
        $this->setApiHost('someplace.test.studypeers.com');
        $this->setToken('super-secret-token-that-you-would-reference-from-a-non-committed-file-of-course'); // please don't commit your token...
    }
}
```

### Instantiate the `CanvasApi` class

1. Choose the Client you want (found in `studypeers\CanvasApi\Clients`), based on the type of call(s) you want to make.

2. Then, choose the Adapter you want to use for the HTTP interaction (found in `studypeers\CanvasApi\Adapters`).

> By default, the `Guzzle` adapter is available with this package (You can write your own; more later).

3. Finally, choose the Config you want to use.

4. Instantiate the `studypeers\CanvasApi\CanvasApi` class, and pass in the above three objects either using a constructor array, or the setter methods:

```php
// OPTION 1 - array in constructor

// instantiate a client
$accountsClient = new \studypeers\CanvasApi\Clients\Accounts;
// instantiate an adapter
$adapter = new \studypeers\CanvasApi\Adapters\Guzzle;
// instantiate a config
$config = new App\CanvasConfigs\TestEnvironment;

// pass as array to API class
$api = new \studypeers\CanvasApi\CanvasApi([
    'client' => $accountsClient,
    'adapter' => $adapter,
    'config' => $config,
]);

```

```php
// OPTION 2 - use setters

// instantiate a client
$accountsClient = new \studypeers\CanvasApi\Clients\Accounts;
// instantiate an adapter
$adapter = new \studypeers\CanvasApi\Adapters\Guzzle;
// instantiate a config
$config = new App\CanvasConfigs\TestEnvironment;

// instantiate API class
$api = new \studypeers\CanvasApi\CanvasApi;
$api->setClient($accountsClient);
$api->setAdapter($adapter);
$api->setConfig($config);

```

Or, take the shortcut and instead pass the class names using either of the methods above:

```php
// OPTION 1 - pass class names

use \studypeers\CanvasApi\Clients\Accounts;
use \studypeers\CanvasApi\Adapters\Guzzle;
use App\CanvasConfigs\TestEnvironment;

// instantiate the API class and pass in the array using class names
$api = new \studypeers\CanvasApi\CanvasApi([
    'client' => Accounts::class,
    'adapter' => Guzzle::class,
    'config' => TestEnvironment::class,
]);
```

```php
// OPTION 2 - use setters
use \studypeers\CanvasApi\Clients\Accounts;
use \studypeers\CanvasApi\Adapters\Guzzle;
use App\CanvasConfigs\TestEnvironment;

$api = new \studypeers\CanvasApi\CanvasApi([
    'client' => Accounts::class,
    'adapter' => Guzzle::class,
    'config' => TestEnvironment::class,
]);
```

Then, once you have the client, if you want to list all Accounts:

```php
// make the call
$result = $accountsClient->listAccounts(); // methods are named as they appear in Canvas API documentation
// get the contents of the result
var_dump($result->getContent()); // you receive a CanvasApiResult object
```

### Client fluent shortcut

You may want to use a different Client while maintaining the same Adapter and Config. You can do this permanently by using the `setClient()` on the API class, or you can do it for a single transaction using the fluent `using()` chainable method.

> `using()` will accept a complete class name (with namespace) for a Client class, or a simplified class name for a built-in Client class, like 'users' or 'quizsubmissions'.

```php
use studypeers\CanvasApi\Clients\Users;
use \studypeers\CanvasApi\Clients\Accounts;
use \studypeers\CanvasApi\Adapters\Guzzle;
use App\CanvasConfigs\TestEnvironment;

$api = new \studypeers\CanvasApi\CanvasApi([
    'client' => Accounts::class,
    'adapter' => Guzzle::class,
    'config' => TestEnvironment::class,
]);

// API class will use Accounts client.
$api->listAccounts();

// use explicit setter - now API class will use Users client for all future calls
$api->setClient(Users::class);
$api->showUserDetails();
$api->getUserSettings(); // still Users client

// OR, use fluent setter with explicit class
$api->using(Users::class)->showUserDetails();
$api->listAccounts(); // back to original Accounts Client

// OR, fluent setter with implied class (from default library)
$api->using('users')->showUserDetails();
$api->listAccounts(); // back to Accounts Client

```

## Setting parameters

Some API calls need additional parameters, and there are two different ways to pass them. The way(s) you choose are directly in line with the following logic:

- If the parameter belongs in the URL, it will be required as an argument to the method call. Example: `getSingleAccount()` calls `api/v1/accounts/:id` where `:id` is the ID of the Account you want to get. Therefore, the signature for the method is `getSingleAccount($id)`, and you would pass in your account value there.

- If the parameter is not contained in the URL, you need to set it as a body parameter. This includes all required parameters as well as optional parameters. Example: `listAccounts()` does not require any parameters, but you may optionally provide an `include` parameter. Use the chainable `addParameters()` method on the client object like so:

```php
$includes = [
    'includes' => [
        'lti_guid',
        'registration_settings',
        'services'
    ],
];

$result = $accountsClient->addParameters($includes)->listAccounts();
```

Refer to the API documentation for the accepted parameters, as well as the structure of the parameters (especially for multi-level nested parameters). This library validates parameters to a point.


# Detailed Usage

## Architecture

### General Architecture

This API is comprised of many **Clients**, each of which interacts with one specific category / area of the Canvas LMS API. For instance, there is a Quizzes client, an Accounts client, a Users client, and so on. Each client class is based fully on its architecture in the Canvas API Documentation. All client classes implement the `studypeers\CanvasApi\Clients\CanvasApiClientInterface` interface. The job of a Client is to return an array that can be parsed into a `CanvasApiEndpoint` object, which requires an endpoint string, HTTP method string, and optional array of required parameters for the call to that Canvas API method.

**Adapter** classes are basically abstracted HTTP request handlers. They worry about structuring and making API calls to Canvas, handling pagination as necessary, and formatting the response in a simple, structured way (using a `CanvasApiResult` object). These Adapter classes implement the `studypeers\CanvasApi\Adapters\CanvasApiAdapterInterface` interface. At this time , only an adapter for [Guzzle](http://docs.guzzlephp.org/en/stable/) is included - however, more can be added later. (Or straight cURL. Nobody's judging.) Adapters technically exist as properties on the Client class.

**Config** classes are classes that configure basic things that the adapter needs to know about in order to interact with the Canvas API. No concrete classes are included in this package - only the abstract `CanvasApiConfig` class. The purpose for this architecture is so that you can create several classes to support different Canvas environments - even if you only interact with one Canvas domain, that domain has a `test` and a `beta` instance.

The **Master API** class is essentially the "traffic controller" class, that worries about making sure the separate components (Client, Adapter, Config) know everything they need to know in order to execute the API call. This class exists as such so that it can be extended as needed. Basically, when asked to make a call, the API class should know everything about what is required to make said API call, including the Client, Adapter, and Config that are being used, the endpoint and method, and so on, making it easy to leverage this class for informational purposes (such as logging or caching... ).

### Naming

Each client's methods are named as closely as possible to the names given to their corresponding operations in the API documentation. For example, the Accounts client contains `listAccounts()`, `listAccountsForCourseAdmins()`, and so on - all in line with the official documentation. The notable modification to this rule: 'a' / 'an' / 'the' are always dropped, so we have `getSingleAccount()`, `getTermsOfService()`, and so forth.

### Aliasing

Where prudent, aliasing has been employed where the Canvas API does not always adhere to logical, RESTful semantic syntax. For instance, while `getSingleAccount()` (as named via the Canvas API documentation) is descriptive, the 'single' modifier is not in line with how traditional RESTful transactions are named, and thus could be considered superfluous; in other words, many developers would guess this to be `getAccount()` instead. So, in these cases, while the original core method will always adhere to its name in the official documentation, there are many places where aliases (like this example) have been added for convenience. These aliases simply call the "real" method and return its results.

## Acting as a user

If you are using this library with a token that includes the permission to "Act As" (formerly "Masquerade" as) another user, you can utilize the chainable `asUser()` method when making a call:

```php
use studypeers\CanvasApi\CanvasApi;

$api = new CanvasApi([
    'config' = new \App\CanvasConfigs\TestEnvironment::class;
    'adapter' = new \studypeers\CanvasApi\Adapters\Guzzle::class;
    'client' = new \studypeers\CanvasApi\Clients\Users::class;
]);

$result = $api->asUser(12345)->listCourseNicknames();
```

## SIS IDs

The Canvas REST API allows you to [substitute a SIS ID](https://canvas.studypeers.com/doc/api/file.object_ids.html) in place of the internal Canvas ID for many items. SIS IDs are supported in this library!

Parameters passed as arguments to all methods are interpolated directly into the URL that is called. Thus, instead of calling `$userClient->getUserSettings(12345)` you could instead call `$userClient->getUserSettings('sis_login_id:jsmith')`, and it would work just fine. See the page above for where SIS IDs can and can't be substituted, and for details on how to handle encoding and escaping as needed.

## Pagination

Pagination is handled automatically, where and when necessary, at the adapter level. Upon each API call the return headers are read, and the Link header is parsed and extracted, [in accordance with the documentation](https://canvas.studypeers.com/doc/api/file.pagination.html).

To customize pagination on any API call, you may utilize `addParameters()` to set the `per_page` value to whatever you choose, or make use of the built-in helper `setPerPage()` method:

```php
$result = $api->addParameters(['per_page' => 100])->listAccounts();
// OR
$result = $api->setPerPage(100)->listAccounts();
```

Since the pagination headers are checked on every API call, each client transaction may therefore initiate several API calls before it reaches completion. See below for details on how results are presented.

## Handling Results

Every time a client transaction is requested, this library will encapsulate important information and present it in the `studypeers\CanvasApi\CanvasApiResult` class. Within that class, you are able to access structured information on each API call made during that transaction, as well as an aggregated resultset that should be iterable as an array. For example, if you ask for all Accounts, and it requires 5 API calls of page size 10 to retrieve them, your result contents would be a single array of all accounts; this is designed to save you time in parsing them yourself!

```php
$result = $api->listAccounts();

// get the result content - your Account objects
$accounts = $result->getContent();

// get a list of the API calls made
$apiCalls = $result->getCalls();
// get the first API call made
$firstCall = $result->getFirstCall();
// get the last API call made
$lastCall = $result->getLastCall();

// get the aggregate status code (success or failure) based on the result of all calls
$status = $result->getStatus();
// get the aggregate message (helpful if there were failures) based on the result of all calls
$status = $result->getMessage();
```

### The API call array

Each API call (retrieved from the `$calls` array on the `CanvasApiResult` object) is made up of some key information that may be useful as you deal with things like throttling (see below), or other meta information on your calls. The API call array stores information on both the request (what is sent) and the response (what is received), and is structured as follows:

```php
[
    'request' => [
        'endpoint'   => $endpoint, // the final assembled endpoint
        'method'     => $method, // get, post, put, delete
        'headers'    => $requestOptions['headers'], // all headers passed - includes bearer info
        'proxy'      => $this->config->getProxy(), // proxy host and port, if using
        'parameters' => $this->parameters, // any parameters used by the client
    ],
    'response' => [
        'headers'              => $response->getHeaders(), // raw headers
        'pagination'           => $this->parse_http_rels($response->getHeaders()), // parsed pagination information
        'code'                 => $response->getStatusCode(), // 200, 403, etc.
        'reason'               => $response->getReasonPhrase(), // OK, Forbidden, etc.
        'runtime'              => $response->getHeader('X-Runtime') ?? '', // convenience item for length of time in seconds the call ran
        'cost'                 => $response->getHeader('X-Request-Cost') ?? '', // convenience item for "cost" of call toward rate limit
        'rate-limit-remaining' => $response->getHeader('X-Rate-Limit-Remaining') ?? '', // convenience item for rate limit bucket level remaining
        'body'                 => json_decode($response->getBody()->getContents()) // raw body content of the response
    ],
]
```

### Rate limit / throttling

This library does not account for Rate Limit, Throttling, or automatic retries / exponential backoff. Most of the time, those issues are only encountered when running scripts that would invoke simultaneous API calls, and will not be a problem even when paginating through a large dataset. From Canvas API documentation:


This library is meant to act as an interface to the Canvas REST API; therefore it does not need to be concerned about how it is being used in client applications. You should take care to handle this in your application code - if rate limits are hit, you can expect this library to tell you by way of the response information on the individual API calls (code 403 with "Rate Limit Exceeded" message).

As a convenience, rate limit information is provided in the base level array of each API call result (see above), so that you do not need to parse through headers yourself.

### Capping resultset size (Max Results)

By default the package ships with a somewhat-sensible default of 9999 maximum results per transaction. Generally speaking, if you're needing more records than this at a time, there is likely a better way to go about getting them (via search query parameters, etc.) than making 100+ paginated API calls. However, you're free to override this to the limits of your imagination (and system memory, and rate limit).

```php
$config->setMaxResults(250);
// OR...
$api->using('users')->setMaxResults(250)->listUserPageViews('jdoe1'); // calling this on the API class will trickle through to the config class (assuming it is set)
```

## Using a proxy

If you need to use an HTTP proxy, set that up in your `CanvasApiConfig` object using `setProxyHost()`, `setProxyPort()`, and `useProxy()`.

## Passing additional headers

If you need to set additional headers on your requests, you can utilize the `setAdditionalHeaders()` method on the client class, which accepts an array of key-value pairs.

## File uploads (multipart data)

This library accommodates the POST method of uploading, as per [Canvas documentation on file uploads](https://canvas.studypeers.com/doc/api/file.file_uploads.html). This process is broken into 2 (or 3) steps:

1. The first API call will "inform" Canvas that you are going to upload a file, and tell it what the file will look like (type of file, desired filename, size, etc.)
2. Canvas will pass back a URL to which the file itself will actually be sent, and a second API call will actually send the data via a multipart form request
3. If the request results in a response code of `3xx` then a third call needs to be made to "finalize" the transaction.

Using this library, you will be able to upload files by doing the following:

* Calling the `uploadFile()` method in the `Files` client, and supplying the `name`, `size`, and `content_type` parameters.
* Inspecting the result to get the new POST URL for the file, as well as the `upload_params`.
* Forming a valid multipart array from the `upload_params`, and appending `file`. One example of a valid array using the included Guzzle adapter:

```
[
    [
        'name' => 'filename',
        'contents' => 'myfile.jpg',
    ],
    [
        'name' => 'content_type',
        'contents' => 'image/jpeg',
    ],
    [
        'name' => 'file',
        'contents' => fopen('/path/to/file', 'r'),
    ],
]
```

* Add the multipart data to the request and execute a call to `uploadFileToUploadUrl()` using the URL given to you.

* if you receive a `3xx` response code, follow the Location header and make a GET request to finalize the transaction. You can do this using the `Base` client.

The process could look like:

```php
use studypeers\CanvasApi\CanvasApi;

$api = new CanvasApi([
    'config' = new \App\CanvasConfigs\TestEnvironment::class;
    'adapter' = new \studypeers\CanvasApi\Adapters\Guzzle::class;
    'client' = new \studypeers\CanvasApi\Clients\Files::class;
]);

$result = $api->addParameters(['name' => 'myfile.jpg', 'size' => '100000', 'content_type' => 'image/jpeg']->uploadFile($folderId); // you may need to act-as the user here too.

// parse out the data
$url = $result->getContent()->upload_url;
$multipartArray = (array) $result->getContent()->upload_params;
$multipartArray['file'] = fopen('/path/to/file.jpg', 'r');

// create a valid multipart array for Guzzle
$multipartArray = array_map(function ($value, $key) {
    return ['name' => $key, 'contents' => $value];
}, $multipartArray, array_keys($multipartArray));

// upload the file
$uploadResult = $api->addMultipart($multipartArray)->uploadFileToUploadUrl($url);

// finalize if needed
$code = $uploadResult->getLastResult()['code'];
if ($code >= 300 && $code < 400) {
    $location = $uploadResult->getLastCall()['headers']['Location'];
    $finalizeResult = $api->using('base')->makeCallToRawUrl($location, 'get');
}
```

# Writing your own Adapters(If you need)



# Extending the API class

//To do

---

# Contributing

//To do

# License

See the [LICENSE](LICENSE.md) file for license rights and limitations (BSD).


