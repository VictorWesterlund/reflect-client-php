<?php

	namespace Reflect;

	class Response {
		public int $status;
		public bool $ok = false;
		
		private mixed $body;

		public function __construct(array $response) {
			// Pass response array into properties
			[$this->status, $this->body] = $response;

			$this->ok = $this->is_ok();
		}

		// A boolean indicating whether the response was successful (status in the range 200 – 299) or not
		public function is_ok(): bool {
			return $this->status >= 200 && $this->status < 300;
		}

		// Parse JSON from response body and return as PHP array
		public function json(bool $assoc = true): array {
			return json_decode($this->body, $assoc);
		}

		// Return response body as-is
		public function text() {
			return $this->body;
		}
	}