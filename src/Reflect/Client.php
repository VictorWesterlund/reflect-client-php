<?php

    namespace Reflect;

    // Allowed HTTP verbs
    enum Method: string {
        case GET     = "GET";
        case POST    = "POST";
        case PUT     = "PUT";
        case DELETE  = "DELETE";
        case PATCH   = "PATCH";
        case OPTIONS = "OPTIONS";
    }

    // Supported connection methods
    enum Connection {
        case HTTP;
        case AF_UNIX;
    }

    class Client {
        // Use this HTTP method if no method specified to call()
        const HTTP_DEFAULT_METHOD = Method::GET;
        // The amount of bytes to read for each chunk from socket
        const SOCKET_READ_BYTES = 2048;

        public function __construct(string $endpoint, string $key = null, Connection $con = null, bool $https_peer_verify = true) {
            $this->_con = $con ?: $this::resolve_connection($endpoint);
            $this->_endpoint = $endpoint;
            $this->_key = $key;

            if ($this->_con === Connection::AF_UNIX) {
                // Connect to Reflect UNIX socket
                $this->_socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
                $conn = socket_connect($this->_socket, $this->_endpoint);
            } else if ($this->_con === Connection::HTTP) {
                // Append tailing "/" for HTTP if absent
                $this->_endpoint = substr($this->_endpoint, -1) === "/" ? $this->_endpoint : $this->_endpoint . "/";
                // Flag which enables or disables SSL peer validation (for self-signed certificates)
                $this->_https_peer_verify = $https_peer_verify;
            }
        }

        // Resolve connection type from endpoint string.
        // If the string is a valid URL we will treat it as HTTP otherwise we will assume it's a path on disk to a UNIX socket file.
        private static function resolve_connection(string $endpoint): Connection {
            return filter_var($endpoint, FILTER_VALIDATE_URL) 
                ? Connection::HTTP 
                : Connection::AF_UNIX;
        }

        // Attempt to resolve Method from backed enum string, or return default
        private static function resolve_method(Method|string $method): Method {
            return ($method instanceof Method) 
                ? $method
                : Method::tryFrom($method) ?? (__CLASS__)::HTTP_DEFAULT_METHOD;
        }

        // Construct stream_context_create() compatible header string
        private function http_headers(): string {
            $headers = ["Content-Type: application/json"];

            // Append Authentication header if API key is provided
            if (!empty($this->_key)) {
                $headers[] = "Authorization: Bearer {$this->_key}";
            }

            // Append new line chars to each header
            $headers = array_map(fn($header): string => $header . "\r\n", $headers);
            return implode("", $headers);
        }

        // Make request and return response over HTTP
        private function http_call(string $endpoint, Method|string $method = (__CLASS__)::HTTP_DEFAULT_METHOD, array $payload = null): array {
            // Resolve string to enum
            $method = $this::resolve_method($method);
            // Remove leading "/" if present, as it's already present in $this->_endpoint
            $endpoint = substr($endpoint, 0, 1) !== "/" ? $endpoint : substr($endpoint, 1, strlen($endpoint) - 1);

            $data = stream_context_create([
                "http" => [
                    "header"        => $this->http_headers(),
                    "method"        => $method->value,
                    "ignore_errors" => true,
                    "content"       => !empty($payload) ? json_encode($payload) : ""
                ],
                "ssl" => [
                    "verify_peer"       => $this->_https_peer_verify,
                    "verify_peer_name"  => $this->_https_peer_verify,
                    "allow_self_signed" => !$this->_https_peer_verify
                ]
            ]);

            $resp = file_get_contents($this->_endpoint . $endpoint, false, $data);

            // Get HTTP response code from $http_response_header which materializes out of thin air after file_get_contents(). 
            // The first header line and second word will contain the status code.
            $resp_code = (int) explode(" ", $http_response_header[0])[1];

            // Return response as [<http_status_code>, <resp_body_assoc_array>]
            return [$resp_code, json_decode($resp)];
        }

        // Make request and return response over socket
        private function socket_txn(string $payload): string {
            $tx = socket_write($this->_socket, $payload, strlen($payload));
            $rx = socket_read($this->_socket, $this::SOCKET_READ_BYTES);

            if (!$tx || !$rx) {
                throw new \Error("Failed to complete transaction");
            }

            return $rx;
        }

        // Call a Reflect endpoint and return response as assoc array
        public function call(string $endpoint, Method|string $method = (__CLASS__)::HTTP_DEFAULT_METHOD, array $payload = null): array {
            // Resolve string to enum
            $method = $this::resolve_method($method);

            // Call endpoint over UNIX socket
            if ($this->_con === Connection::AF_UNIX) {
                // Return response as assoc array
                return json_decode($this->socket_txn(
                    // Send request as stringified JSON
                    json_encode([
                        $endpoint,
                        $method->value,
                        $payload
                    ])
                ), true);
            }

            // Call endpoint over HTTP
            return $this->http_call(...func_get_args());
        }
    }