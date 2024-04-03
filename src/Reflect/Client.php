<?php

    namespace Reflect;

    use Reflect\Method;
    use Reflect\Response;

    require_once "Method.php";
    require_once "Response.php";

    class Client {
        private string $params;
        private string $endpoint;
        private array $headers = [];
        private ?array $payload = null;

        protected ?string $key;
        protected string $base_url;
        protected bool $https_verify_peer;
            
        public function __construct(string $base_url, string $key = null, bool $verify_peer = true) {
            // Set optional API key and Authorization header
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
        private function get_headers(): string {
            // Set Authorization header if an API key is used
            if ($this->key) {
                $this->headers["Authorization"] = "Bearer {$this->key}";
            }

            
            // Construct HTTP headers string from array
            $headers = array_map(fn(string $k, string $v): string => "{$k}: {$v}\r\n", array_keys($this->headers), array_values($this->headers));
            return implode("", $headers);
        }

        // Make request and return response over HTTP
        private function http_call(Method $method): array {
            $context = stream_context_create([
                "http" => [
                    "header"        => $this->get_headers(),
                    "method"        => $method->name,
                    "ignore_errors" => true,
                    "content"       => !empty($this->payload) ? json_encode($this->payload) : ""
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

        // Set request body to be JSON-stringified
        private function set_request_body(?array $payload = null): self {
            // Unset request body if no payload defined
            if (empty($payload)) {
                $this->payload = null;
                return $this;
            }

            $this->headers["Content-Type"] = "application/json";

            $this->payload = $payload;
            return $this;
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

            // Reset initial values
            $this->params();
            $this->headers = [];
            $this->set_request_body();

            return $this;
        }

        // ----

        // Make a GET request to endpoint with optional search parameters
        public function get(): Response {
            return new Response(...$this->http_call(Method::GET));
        }

        public function patch(?array $payload = []): Response {
            $this->set_request_body($payload);
            return new Response(...$this->http_call(Method::PATCH));
        }

        public function put(?array $payload = []): Response {
            $this->set_request_body($payload);
            return new Response(...$this->http_call(Method::PUT));
        }

        public function post(?array $payload = []): Response {
            $this->set_request_body($payload);
            return new Response(...$this->http_call(Method::POST));
        }

        public function delete(?array $payload = []): Response {
            $this->set_request_body($payload);
            print_r($this->headers);
            return new Response(...$this->http_call(Method::DELETE));
        }
    }