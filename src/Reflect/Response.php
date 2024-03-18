<?php

	namespace Reflect;

	class Response {
		public int $code;
		public bool $ok = false;
		
		private mixed $body;

		public function __construct(string|array $resp, int $code = 200) {
			// Get response body from array or directly from string
			$this->body = is_array($resp) ? $resp[0] : $resp;
			// Set response code from array or with method argument
			$this->code = is_array($resp) ? (int) $resp[1] : $code;

			// Response code is within the Success range
			$this->ok = $this->code >= 200 && $this->code < 300;
		}

		// Parse JSON from response body and return as PHP array
		public function json(bool $assoc = true): array {
			return json_decode($this->body, $assoc);
		}

		// Return response body as-is
		public function output() {
			return $this->body;
		}
	}