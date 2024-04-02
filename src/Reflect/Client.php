<?php

    namespace Reflect;

    use Reflect\Method;
    use Reflect\Response;

    require_once "Method.php";
    require_once "Response.php";

    class Client {
        protected ?string $key;
        protected string $base_url;
        protected bool $https_verify_peer;

        protected string $endpoint;
        protected string $params;
            
        public function __construct(string $base_url, string $key = null, bool $verify_peer = true) {
            // Optional API key
            $this->key = $key;

            // Append tailing "/" if absent
            $this->base_url = substr($base_url, -1) === "/" ? $base_url : $base_url . "/";
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
        private function http_call(Method $method, array $payload = null): array {
            $context = stream_context_create([
                "http" => [
                    "header"        => $this->http_headers(),
                    "method"        => $method->name,
                    "ignore_errors" => true,
                    "content"       => !empty($payload) ? json_encode($payload) : ""
                ],
                "ssl" => [
                    "verify_peer"       => $this->https_verify_peer,
                    "verify_peer_name"  => $this->https_verify_peer,
                    "allow_self_signed" => !$this->https_verify_peer
                ]
            ]);

            $resp = file_get_contents(implode("", [$this->base_url, $this->endpoint, $this->params]), false, $context);

            // Get HTTP response code from $http_response_header which materializes out of thin air after file_get_contents(). 
            // The first header line and second word will contain the status code.
            $resp_code = (int) explode(" ", $http_response_header[0])[1];

            // Return response as [<resp_body_assoc_array>, <http_status_code>]
            return [$resp, $resp_code];
        }

        // ----

        // Construct URL search parameters from array if set
        public function params(?array $params = null): self {
            $this->params = !empty($params) ? self::get_params($params) : "";
            return $this;
        }

        // Create a new call to an endpoint
        public function call(string $endpoint): self {
            // Remove leading "/" if present, as it's already present in $this->base_url
            $this->endpoint = substr($endpoint, 0, 1) !== "/" 
                ? $endpoint 
                : substr($endpoint, 1, strlen($endpoint) - 1);

            // Reset search parameters
            $this->params();

            return $this;
        }

        // ----

        // Make a GET request to endpoint with optional search parameters
        public function get(): Response {
            return new Response(...$this->http_call(Method::GET));
        }

        public function patch(?array $payload = []): Response {
            return new Response(...$this->http_call(Method::PATCH, $payload));
        }

        public function put(?array $payload = []): Response {
            return new Response(...$this->http_call(Method::PUT, $payload));
        }

        public function post(?array $payload = []): Response {
            return new Response(...$this->http_call(Method::POST, $payload));
        }

        public function delete(?array $payload = []): Response {
            return new Response(...$this->http_call(Method::DELETE, $payload));
        }
    }