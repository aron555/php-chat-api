<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "jwt_secret_key";

function signJwtForUser($user): string
{
    if (is_null($user["id"])) {
        throw new Exception("Used id not set");
    }

    $jwtPayload = [
        "id" => $user["id"],
        "username" => $user["username"],
        "createdAt" => time(),
    ];

    return jwtEncode($jwtPayload);
}

function jwtEncode($payload): string
{
    global $key;

    return JWT::encode($payload, $key);
}

function jwtDecode($jwt): object
{
    global $key;

    return JWT::decode($jwt, new Key($key, 'HS256'));
}

