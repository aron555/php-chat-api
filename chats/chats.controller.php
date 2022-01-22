<?php
  
  @include_once __DIR__ . "/../utils/httpException.php";
  @include_once __DIR__ . "/../utils/jsonResponse.php";
  @include_once __DIR__ . "/chats.service.php";
  @include_once __DIR__ . "/../locale/en/messages.php";
  @include_once __DIR__ . "/../utils/randomId.php";
  
  class ChatsController
  {
    private $chatsService;
    
    function __construct($conn)
    {
      $this->chatsService = new ChatsService($conn);
    }
    
    function getChatById($req)
    {
      global $messages;
      
      # Parse chat id from url
      $chatId = substr($req['resource'], strlen('/api/chats/'));

      $chat = $this->chatsService->findById($chatId);

      if (is_null($chat)) {
        httpException($messages["chat_not_found"], 404)['end']();
      }
      
      if ($chat["isPrivate"] && !$this->chatsService->isUserChatParticipant($req["user"]["id"], $chatId)) {
        httpException($messages["no_access_to_the_chat"], 401)['end']();
      }

      $response = [
        "chat" => $this->chatsService->createChatRO($chat)
      ];
      
      jsonResponse($response)['end']();
    }
    
    function createChat($req, $chatDto)
    {
      global $messages;
      
      try {
        $chat = $chatDto;
  
        $chat["id"] = randomId();
        $chat["inviteLink"] = randomId();
        
        $this->chatsService->createChat($chat["id"], $chatDto["name"], $chatDto["isPrivate"], $chat["inviteLink"]);
        
        $this->chatsService->addParticipantToChat($req["user"]["id"], $chat["id"], 2);
        
        $response = [
          "message" => $messages["chat_created"],
          "chat" => $chat
        ];
  
        jsonResponse($response, 201)['end']();
      }
      catch (PDOException $ex)
      {
        httpException($messages["failed_to_create_chat"])['end']();
      }
    }
    
    function deleteChat($req)
    {
      global $messages;
      
      try {
        $chatId = substr($req['resource'], strlen('/api/chats/'));
  
        $chat = $this->chatsService->findById($chatId);
  
        if (is_null($chat)) {
          httpException($messages["chat_not_found"], 404)['end']();
        }
        
        $chatParticipant = $this->chatsService->getChatParticipantByUserId($req["user"]["id"], $chatId);
        
        if (is_null($chatParticipant) || intval($chatParticipant["permission"]) !== 2)
        {
          httpException($messages["not_enough_permission"], 403)['end']();
        }
        
        $this->chatsService->deleteChatById($chatId);
  
        $response = [
          "message" => $messages["chat_deleted"]
        ];
        
        jsonResponse($response)['end']();
      }
      catch (PDOException $ex)
      {
        httpException($messages["failed_to_delete_chat"])['end']();
      }
    }
    
    function deleteChatParticipant($req) {
      global $messages;
  
      try {
        preg_match("/\/api\/chats\/(?'chatId'[a-z0-9]+)\/users\/(?'userId'[a-z0-9]+)/", $req['resource'], $parsedUrl);
    
        $chat = $this->chatsService->findById($parsedUrl["chatId"]);
    
        if (is_null($chat)) {
          httpException($messages["chat_not_found"], 404)['end']();
        }
        
        $initiatorParticipant = $this->chatsService->getChatParticipantByUserId($req["user"]["id"], $chat["id"]);
        
        if (is_null($initiatorParticipant) || intval($initiatorParticipant["permission"]) !== 2)
        {
          httpException($messages["not_enough_permission"], 403)['end']();
        }
    
        $chatParticipantToDelete = $this->chatsService->getChatParticipantByUserId($parsedUrl["userId"], $chat["id"]);
    
        if (is_null($chatParticipantToDelete))
        {
          httpException($messages["participant_not_found"], 404)['end']();
        }
    
        $this->chatsService->deleteChatParticipant($parsedUrl["userId"], $chat["id"]);
    
        $response = [
          "message" => $messages["participant_deleted"]
        ];
    
        jsonResponse($response)['end']();
      }
      catch (PDOException $ex)
      {
        var_dump($ex);
        httpException($messages["failed_to_delete_chat_participant"])['end']();
      }
    }
  }
