<?php

    namespace Reflect;

    use Reflect\Method;
    use Reflect\Response;

    require_once "Method.php";
    require_once "Response.php";

    class Client {
        private ?string $key;
        private string $base_url;
        private bool $https_verify_peer;
            
        public function __construct(string $base_url, string $key = null, bool $verify_peer = true) {
            // Optional API key
            $this->key = $key;

            // Append tailing "/" if absent
            $this->base_url = substr($this->base_url, -1) === "/" ? $this->base_url : $this->base_url . "/";
            // Flag which enables or disables SSL peer validation (for self-signed certificates)
            $this->https_verify_peer = $verify_peer;
        }

        // Convert assoc array to URL-encoded string or empty string if array is empty
        private static function get_params(array $params): string {
            return !empty($params) ? "?" . http_build_query($params) : "";
        }

        // ----

        // Construct stream_context_create() compatible header string
        private function http_headers(): string {
            $headers = ["Content-Type: application/json"];

            // Append Authentication header if API key is provided
            if (!empty($this->key)) {
                $headers[] = "Authorization: Bearer {$this->key}";
            }

            // Append new line chars to each header
            $headers = array_map(fn($header): string => $header . "\r\n", $headers);
            return implode("", $headers);
        }

        // Make request and return response over HTTP
        private function http_call(string $endpoint, Method|string $method = self::HTTP_DEFAULT_METHOD, array $payload = null): array {
            // Resolve string to enum
            $method = self::resolve_method($method);
            // Remove leading "/" if present, as it's already present in $this->endpoint
            $endpoint = substr($endpoint, 0, 1) !== "/" ? $endpoint : substr($endpoint, 1, strlen($endpoint) - 1);

            $data = stream_context_create([
                "http" => [
                    "header"        => $this->http_headers(),
                    "method"        => $method->value,
                    "ignore_errors" => true,
                    "content"       => !empty($payload) ? json_encode($payload) : ""
                ],
                "ssl" => [
                    "verify_peer"       => $this->https_verify_peer,
                    "verify_peer_name"  => $this->https_verify_peer,
                    "allow_self_signed" => !$this->https_verify_peer
                ]
            ]);

            $resp = file_get_contents($this->endpoint . $endpoint, false, $data);

            // Get HTTP response code from $http_response_header which materializes out of thin air after file_get_contents(). 
            // The first header line and second word will contain the status code.
            $resp_code = (int) explode(" ", $http_response_header[0])[1];

            // Return response as [<http_status_code>, <resp_body_assoc_array>]
            return [$resp, $resp_code];
        }

        // ----

        // Make a GET request to endpoint with optional search parameters
        public function get(string $endpoint, array $params = []): Response {
            $resp = $this->http_call($endpoint . self::get_params($params), Method::GET);
            return new Response(...$resp);
        }

        public function patch(string $endpoint, array $params, array $payload): Response {
            $resp = $this->http_call($endpoint . self::get_params($params), Method::PATCH, $payload);
            return new Response(...$resp);
        }

        public function put(string $endpoint, array $params, array $payload): Response {
            $resp = $this->http_call($endpoint . self::get_params($params), Method::PUT, $payload);
            return new Response(...$resp);
        }

        public function post(string $endpoint, array $params, array $payload): Response {
            $resp = $this->http_call($endpoint . self::get_params($params), Method::POST, $payload);
            return new Response(...$resp);
        }

        public function delete(string $endpoint, array $params, ?array $payload = []): Response {
            $resp = $this->http_call($endpoint . self::get_params($params), Method::POST, $payload);
            return new Response(...$resp);
        }
    }