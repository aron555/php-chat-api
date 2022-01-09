<?php
  
  @include_once __DIR__ . "/../utils/httpException.php";
  @include_once __DIR__ . "/../utils/jsonResponse.php";
  @include_once __DIR__ . "/users.service.php";
  @include_once __DIR__ . "/../locale/en/messages.php";
  
  class UsersController
  {
    private $usersService;
    
    function __construct($conn)
    {
      $this->usersService = new UsersService($conn);
    }
    
    function getUsers()
    {
      $users = $this->usersService->getUsers();
      
      $response = [
        "users" => $users
      ];
      
      jsonResponse($response)['end']();
    }
    
    function getUserById($req)
    {
      global $messages;
      
      # Parse user id from url
      $userId = intval(substr($req['resource'], strlen('/api/users/')));
      
      $user = $this->usersService->getUserById($userId);
      
      if (is_null($user)) {
        httpException($messages["user_not_found"], 404)['end']();
      }
      
      $response = [
        "user" => $this->usersService->createUserRO($user)
      ];
      
      jsonResponse($response)['end']();
    }
    
    function createUser($userDto)
    {
      $result = $this->usersService->createUser($userDto);
      
      if (!$result) {
        httpException("Failed to create user")['end']();
      }
      
      $response = [
        "message" => "User created",
        "user" => $result
      ];
      
      jsonResponse($response)['end']();
    }
    
    function getMe($bearer)
    {
      global $messages;
      
      $isValidBearer = strpos($bearer, "Bearer ");
      
      if (!$isValidBearer && $isValidBearer !== 0) {
        httpException($messages["not_authenticated"], 401)['end']();
      }
      
      // 7 - is "Bearer " string length
      $jwt = substr($bearer, 7);
      
      if (strlen($jwt) <= 0) {
        httpException($messages["not_authenticated"], 401)['end']();
      }
      
      $decodedJwt = [];
      
      try {
        $decodedJwt = jwtDecode($jwt);
      } catch (Exception $exception) {
        httpException($messages["not_authenticated"], 401)['end']();
      }
      
      # Get user from db
      $user = $this->usersService->getUserById($decodedJwt->id);
      
      $response = ["user" => $this->usersService->createUserRO($user)];
      
      jsonResponse($response);
    }
  }
