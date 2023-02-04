<?php

@include_once "./vendor/autoload.php";

// @include_once "./utils/httpException.php";

@include_once "./utils/request.php";

@include_once "./guards/jwtAuthGuard.php";

@include_once "./users/users.controller.php";

@include_once "./db/db.controller.php";

@include_once "./chats/chats.controller.php";

@include_once "./messages/messages.controller.php";

@include_once "./auth/auth.controller.php";


class AppController
{
    # Connection
    private PDO $conn;
    # Request object
    private Request $_req;
    # Request array returned by getRequest() method
    # Also, note that id doesn't contain user unless you update its value manually
    private array $req;
    # Controllers
    private DbController $dbController;
    private UsersController $usersController;
    private ChatsController $chatsController;
    private AuthController $authController;
    private MessagesController $messagesController;
    # Guards
    private JwtAuthGuard $jwtAuthGuard;

    public function __construct($dbConfig)
    {
        # Setup headers and db
        $this->setHeaders();

        # Setup db
        $this->dbController = new DbController($dbConfig);
        $this->conn = $this->dbController->getConnection();

        # Initialize controllers
        $this->usersController = new UsersController($this->conn);
        $this->authController = new AuthController($this->conn);
        $this->chatsController = new ChatsController($this->conn);
        $this->messagesController = new MessagesController($this->conn);

        # Initialize guards
        $this->jwtAuthGuard = new JwtAuthGuard(new UsersService($this->conn));

        # Parse request
        $this->_req = new Request($_SERVER);
        $this->req = $this->_req->getRequest();

        # Routing
        $this->router();
    }

    private function setHeaders()
    {
        header('Content-Type: application/json');
    }

    private function router()
    {
        switch ($this->req['method']) {
            case 'GET':
                if ($this->req['resource'] === '/api/users/me') {
                    // When we require authorization and want to get user in controller
                    $this->_req->useGuard($this->jwtAuthGuard);
                    // We need to call getRequest() method in original request method
                    $this->usersController->getMe($this->_req->getRequest());
                    return;
                }
                # /users/me/chats
                if ($this->req['resource'] === '/api/users/me/chats') {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->usersController->getUserChats($this->_req->getRequest());
                    return;
                }
                # /users/:userId
                if (strpos($this->req['resource'], '/api/users/') === 0) {
                    $this->usersController->getUserById($this->req);
                    return;
                }
                if ($this->req['resource'] === '/api/users') {
                    $this->usersController->getUsers();
                    return;
                }
                # /chats/:chatId/users
                if (preg_match("/\/api\/chats\/(?'chatId'[a-z0-9]+)\/users/", $this->req['resource'])) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->chatsController->getChatParticipants($this->_req->getRequest());
                    return;
                }
                if (preg_match("/\/api\/chats\/(?'chatId'[a-z0-9]+)\/messages/", $this->req['resource'])) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->messagesController->getChatMessages($this->_req->getRequest());
                    return;
                }
                # /chats/:chatId
                if (strpos($this->req['resource'], '/api/chats/') === 0) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->chatsController->getChatById($this->_req->getRequest());
                    return;
                }

                httpException("Route not found " . $this->req['resource'], 404)['end']();
                logMessage("Route not found $this->req");

                break;

            case 'POST':
                $reqBody = $this->_req->parseBody();

                if (preg_match("/\/api\/chats\/(?'chatId'[a-z0-9]+)\/users/", $this->req['resource'])) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->chatsController->addUserToChat($this->_req->getRequest(), $reqBody["data"]);
                    return;
                }
                if (preg_match("/\/api\/chats\/(?'chatId'[a-z0-9]+)\/messages/", $this->req['resource'])) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->messagesController->createMessage($this->_req->getRequest(), $reqBody["data"]);
                    return;
                }
                if ($this->req['resource'] === '/api/chats') {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->chatsController->createChat($this->_req->getRequest(), $reqBody["data"]);
                    return;
                }
                if ($this->req['resource'] === '/api/auth/sign-up') {
                    $this->authController->signUp($reqBody["data"]);
                    return;
                }
                if ($this->req['resource'] === '/api/auth/sign-in') {
                    $this->authController->signIn($reqBody["data"]);
                    return;
                }

                httpException("Route not found", 404)['end']();

                break;

            case 'DELETE':
                $reqBody = $this->_req->parseBody();

                if (preg_match("/\/api\/chats\/(?'chatId'[a-z0-9]+)\/users\/(?'userId'[a-z0-9]+)/", $this->req['resource'])) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->chatsController->deleteChatParticipant($this->_req->getRequest());
                    return;
                }
                if (strpos($this->req['resource'], '/api/chats/') === 0) {
                    $this->_req->useGuard($this->jwtAuthGuard);
                    $this->chatsController->deleteChat($this->_req->getRequest());
                    return;
                }

                break;

            default:
                httpException("Method not supported", 404)['end']();

                break;
        }
    }
}
