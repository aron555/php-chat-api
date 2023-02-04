<?php

@include_once "./jsonResponse.php";

function httpException($data, $statusCode = 400): array
{
    return jsonResponse(["error" => $data], $statusCode);
}
