# Reflect API UNIX socket client for PHP

Make requests to endpoints created with the [Reflect API framework](https://github.com/victorwesterlund/reflect) running the same machine.

---

Make a request with `SocketClient->call()`. It will return the response as an array of length 2.
- The first value is the HTTP-equivalent response code.
- The second value is the response body

```php
$client = new Reflect\SocketClient("/path/to/socket");

$client->call("foo", Method::GET); // (array) [200, "bar"]
$client->call("foo", Method::POST, [
  "foo" => "bar"
]); // (array) [201, "Created"]

// etc..
```

## How to use

Requires PHP 8.1 or newer, and of course an instance of the [Reflect socket server](https://github.com/VictorWesterlund/reflect/wiki/UNIX-Sockets) running on the same machine.

1. **Install with composer**

   ```
   composer require reflect/socket-server
   ```
   
2. **Initialize the class**

   ```php
   require_once "/vendor/autoload.php";
   
   $client = new Reflect\SocketClient("/path/to/socket");
   ```
   
3. **Make API request**

   Use the `call()` method to perform a request
   
   ```php
   $client->call("foo?bar=baz", Method::GET);
   ```
   
   Argument index|Type|Required|Description
   --|--|--|--
   0|String|Yes|Fully qualified pathname and query params of the endpoint to call
   1|Method|Yes|A supported [Reflect HTTP method](https://github.com/VictorWesterlund/reflect/wiki/Supported-technologies#http-request-methods) (eg. `Method::GET`)
   2|Array|No|An optional indexed, associative, or multidimensional array that will be sent as the request body as `Content-Type: application/json`
   
   The `call()` function will return an array of length 2 wil the following information
   
   Index|Type|Description
   --|--|--
   0|Int|HTTP-equivalent response code (eg. `200` or `404`)
   1|String/Array|Contains the response body as either a string, or array if the response `Content-Type` header is `application/json`
   
## How to use (CLI)

You can also run this from the command line with

```
php client <socket_file> <endpoint> <http_method> [payload]
```

and it will return a serialized JSON array with the same structure as described in the `SocketClient->call()` return.

*Example*
```sh
php client "/run/reflect.sock" "foo?bar=biz" "POST" "[\"foo\" => \"bar\"]" # (string) [201, \"Created\"]
```

---

Requires PHP CLI 8.1 or greater, and of course an instance of the [Reflect socket server](https://github.com/VictorWesterlund/reflect/wiki/UNIX-Sockets) running on the same machine.

1. **Clone repo**

   ```
   git clone https://github.com/victorwesterlund/reflect-socket-client-php
   ```
   
2. **Run from command line**

   ```
   cd reflect-socket-client-php
   php client <socket_file> <endpoint> <http_method> [payload]
   ```
