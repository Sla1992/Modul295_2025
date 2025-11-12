<?php
// Error Handling Class, it handles exceptions and returns the responses in JSON format
class ErrorHandler {
    public static function handleException(Throwable $exception): void
    {
        http_response_code(500);
        $payload = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        exit;
    }
}