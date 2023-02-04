<?php

@include_once __DIR__ . "/../utils/httpException.php";
@include_once __DIR__ . "/../utils/jsonResponse.php";
@include_once __DIR__ . "/../utils/jwt.php";
@include_once __DIR__ . "/../locale/en/messages.php";
@include_once __DIR__ . "/../utils/randomId.php";

class AuthController
{
    private UsersService $usersService;

    public function __construct($conn)
    {
        $this->usersService = new UsersService($conn);
    }

    function signUp($registerUserDto): array
    {
        global $messages;

        // TODO:
        // - encrypt password

        $foundUser = $this->usersService->getUserByUsername($registerUserDto["username"]);

        if (!is_null($foundUser)) {
            httpException($messages["username_taken"])['end']();
        }

        $id = randomId();

        try {
            $this->usersService->createUser($id, $registerUserDto["username"], $registerUserDto["password"]);
        } catch (Exception $exception) {
            httpException($messages["failed_to_sign_up"])['end']();
        }

        $createdUser = $this->usersService->getUserById($id);

        $jwt = signJwtForUser($createdUser);

        $response = [
            "jwt" => $jwt
        ];

        return jsonResponse($response);
    }

    public function signIn($loginUserDto): array
    {
        global $messages;

        // TODO:
        // - check encrypted password match

        $foundUser = $this->usersService->getUserByUsername($loginUserDto["username"]);

        if (is_null($foundUser)) {
            httpException($messages["user_not_found"])['end']();
        }

        if ($foundUser["password"] !== $loginUserDto["password"]) {
            httpException($messages["failed_to_sign_in"], 401)['end']();
        }

        $jwt = signJwtForUser($foundUser);

        $response = [
            "jwt" => $jwt
        ];

        return jsonResponse($response);
    }
}
