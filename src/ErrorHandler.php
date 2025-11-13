<?php
// Error Handling Class, it handles exceptions and returns the responses in JSON format
class ErrorHandler
{
    // Handle uncaught exceptions
    public static function handleException(Throwable $exception): void
    {
        // Set the HTTP response code to 500 (Internal Server Error)
        http_response_code(500);
        // Prepare the error payload with exception details
        $payload = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        // Set the response header to indicate JSON content
        header('Content-Type: application/json; charset=UTF-8');
        // Output the error payload in JSON format
        echo json_encode($payload);
        // Terminate the script
        exit;
    }
}