<?php

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
    enum ConType {
        case HTTP;
        case AF_UNIX;
    }

    class Client {
        public function __construct(string $endpoint, ConType $con = null) {
            $this->_con = $con ?: $this::resolve_contype($endpoint);
            $this->_endpoint = $endpoint;

            // Initialize socket properties
            if ($this->_con === ConType::AF_UNIX) {
                $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
                $conn = socket_connect($this->socket, $this->_endpoint);
            }
        }

        // Resolve connection type from endpoint string.
        // If the string is a valid URL we will treat it as HTTP otherwise we will
        // assume it's a path on disk to a UNIX socket file.
        private static function resolve_contype(string $endpoint): ConType {
            return filter_var($endpoint, FILTER_VALIDATE_URL) 
                ? ConType::HTTP 
                : ConType::AF_UNIX;
        }

        // Make request and return response over HTTP
        private function http_call(string $endpoint, Method $method, array $payload = null): array {
            $data = stream_context_create([
                "http" => [
                    "header"        => "Content-Type: application/json",
                    "method"        => $method->value,
                    "ignore_errors" => true,
                    "content"       => !empty($payload) ? json_encode($payload) : ""
                ]
            ]);

            $resp = file_get_contents($this->_endpoint . $endpoint, false, $data);

            // Get HTTP response code from $http_response_header which materializes out of
            // thin air after file_get_contents(). The first header line and second word will
            // contain the status code.
            $resp_code = (int) explode(" ", $http_response_header[0])[1];

            return [$resp_code, $resp];
        }

        // Make request and return response over socket
        private function socket_txn(string $payload): string {
            $tx = socket_write($this->socket, $payload, strlen($payload));
            $rx = socket_read($this->socket, 2024);

            if (!$tx || !$rx) {
                throw new Error("Failed to complete transaction");
            }

            return $rx;
        }

        // Create HTTP-like JSON with ["<endpoint>","<method>","[payload]"] and return
        // respone from endpoint as ["<http_status_code", "<json_encoded_response_body>"]
        public function call(string $endpoint, Method $method, array $payload = null): array {
            // Call endpoint over UNIX socket
            if ($this->_con === ConType::AF_UNIX) {
                return json_decode($this->socket_txn(json_encode([
                    $endpoint,
                    $method->value,
                    $payload
                ])));
            }

            // Call endpoint over HTTP
            return $this->http_call(...func_get_args());
        }
    }