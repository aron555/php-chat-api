<?php
  
  use Firebase\JWT\JWT;
  use Firebase\JWT\Key;
  
  $key = "jwt_secret_key";
  
  function signJwtForUser($user): string
  {
    $jwtPayload = array(
      "username" => $user["username"],
      "createdAt" => time(),
    );
  
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
    
    $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    print_r($decoded);
    
    return $decoded;
  }

