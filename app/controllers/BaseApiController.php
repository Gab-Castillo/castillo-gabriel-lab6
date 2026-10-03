<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Shared helpers for the JSON API controllers.
 */
class BaseApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->database();
        $this->call->library('api'); // sends CORS headers and checks the JWT secrets
    }

    /**
     * Read the JSON request body untouched.
     * (Api::body() HTML-escapes values, which would turn "A&B" into "A&amp;B" in the database.)
     */
    protected function json_input(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    protected function ok($data = null, string $message = 'OK', int $code = 200): void
    {
        $this->api->respond(['status' => $code, 'message' => $message, 'data' => $data], $code);
    }

    protected function fail_validation(array $errors): void
    {
        $this->api->respond([
            'error'  => 'Validation failed',
            'status' => 422,
            'errors' => $errors,
        ], 422);
    }
}
