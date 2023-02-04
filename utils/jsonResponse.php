<?php

function jsonResponse($data, $statusCode = 200): array
{
    header('Content-Type: application/json');
    http_response_code($statusCode);

    echo json_encode([
        "data" => $data,
        "statusCode" => $statusCode
    ]);

    return [
        "end" => function () {
            exit;
        }
    ];
}
