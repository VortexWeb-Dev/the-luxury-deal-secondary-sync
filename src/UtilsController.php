<?php
define('CONFIG', require_once __DIR__ . '/../config.php');

class UtilsController
{
    // Parses incoming JSON data
    public static function parseRequestData(): ?array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        return $data;
    }


    // Sends response back to the webhook
    public static function sendResponse(int $statusCode, array $data): void
    {
        header("Content-Type: application/json");
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Checks if the deal is in the correct stage
    public static function isValidDeal(array $deal): bool
    {
        return isset($deal['STAGE_ID']) && $deal['STAGE_ID'] === CONFIG['DIRECTORS_APPROVAL_STAGE_ID'];
    }
}
