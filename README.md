# Reflect API client for PHP

Make requests from a PHP application to an API built with the [Reflect API framework](https://github.com/VictorWesterlund/reflect).

## Installation

1. **Install with composer**
```
composer require reflect/client
```

2. **`use` in your PHP code**
```php
use Reflect\Client;

$client = new Client(string $api_base_url, ?string $api_key, ?bool $verify_peer = true);
```

## Making API calls

Start by initializing the `Client` with a base URL to the API, and with an optional API key.

```php
use Reflect\Client;
use Reflect\Method;

$client = new Client("https://api.example.com", "MyApiKey");
```

### Defining an endpoint

Start a new API call by chaining the `call()` method and passing it an endpoint string

```php
Client->call(string $endpoint): self
```

Example:

```php
$client->call("my/endpoint");
```

### (Optional) Search Parameters

Pass an associative array of keys and values to `params()`, and chain it anywhere before a `get()`, `patch()`, `put()`, `post()`, or `delete()` request to set search (`$_GET`) parameters for the current request.

```php
Client->params(?array $params = null): self
```

Example:

```php
// https://api.example.com/my/endpoint?key1=value1&key2=value2
$client->call("my/endpoint")
  ->params([
    "key1" => "value1",
    "key2" => "value2"
  ]);
```

### `GET` Request

Make a `GET` request by chaining `get()` at the end of a method chain. This method will return a `Reflect\Response` object.

```php
Client->get(): Reflect\Response;
```

Example:

```php
$client->call("my/endpoint")->params(["foo" => "bar"])->get();
```

### `POST` Request

Make a `POST` request by chaining `post()` at the end of a method chain. This method will return a `Reflect\Response` object.

Pass `post()` a stringifiable associative array of key, value pairs to be sent as an `application/json`-encoded request body to the endpoint.

```php
Client->post(array $payload): Reflect\Response;
```

Example:

```php
$client->call("my/endpoint")->params(["foo" => "bar"])->post(["baz" => "qux"]);
```

### `PATCH` Request

Make a `PATCH` request by chaining `patch()` at the end of a method chain. This method will return a `Reflect\Response` object.

Pass `patch()` a stringifiable associative array of key, value pairs to be sent as an `application/json`-encoded request body to the endpoint.

```php
Client->patch(array $payload): Reflect\Response;
```

Example:

```php
$client->call("my/endpoint")->params(["foo" => "bar"])->patch(["baz" => "qux"]);
```

### `PUT` Request

Make a `PUT` request by chaining `put()` at the end of a method chain. This method will return a `Reflect\Response` object.

Pass `put()` a stringifiable associative array of key, value pairs to be sent as an `application/json`-encoded request body to the endpoint.

```php
Client->put(array $payload): Reflect\Response;
```

Example:

```php
$client->call("my/endpoint")->params(["foo" => "bar"])->put(["baz" => "qux"]);
```

### `DELETE` Request

Make a `DELETE` request by chaining `delete()` at the end of a method chain. This method will return a `Reflect\Response` object.

Pass `delete()` an optional stringifiable associative array of key, value pairs to be sent as an `application/json`-encoded request body to the endpoint.

```php
Client->delete(?array $payload = null): Reflect\Response;
```

Example:

```php
$client->call("my/endpoint")->params(["foo" => "bar"])->delete();
```
