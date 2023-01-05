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

    class SocketClient {
        public function __construct(string $path) {
            $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            $conn = socket_connect($this->socket, $path);

            if (!$conn) {
                throw new Error("Unable to connect to socket");
            }
        }

        // Make request and return response
        private function txn(string $payload): string {
            $tx = socket_write($this->socket, $payload, strlen($payload));
            $rx = socket_read($this->socket, 2024);

            if (!$tx || !$rx) {
                throw new Error("Failed to complete transaction");
            }

            return $rx;
        }

        // Create HTTP-like JSON with ["<endpoint>","<method>","[payload]"]
        public function call(string $endpoint, Method $method, array $payload = null): mixed {
            return json_decode($this->txn(json_encode([
                $endpoint,
                $method->value,
                $payload
            ])));
        }
    }