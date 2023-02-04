<?php

class MessagesService
{
    private PDO $conn;

    function __construct($conn)
    {
        $this->conn = $conn;
    }

    function createMessage($id, $chatId, $userId, $content, $contentType)
    {
        $sql = "INSERT INTO Messages (id, chatId, userId, content, contentType, createdAt) VALUES (:id, :chatId, :userId, :content, :contentType, :createdAt)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id);
        $stmt->bindValue(":chatId", $chatId);
        $stmt->bindValue(":userId", $userId);
        $stmt->bindValue(":content", $content);
        $stmt->bindValue(":contentType", $contentType);
        $stmt->bindValue(":createdAt", time());
        $stmt->execute();
    }

    function getChatMessages($chatId): array
    {
        $sql = <<<SQL
        SELECT
          M.id AS id,
          M.content AS content,
          M.contentType AS contentType,
          M.createdAt AS createdAt,
          U.id AS user_id,
          U.fullname AS user_fullname,
          U.username AS user_username,
          U.profileImage AS user_profileImage
        FROM Messages AS M, Users AS U
        WHERE chatId=:chatId
        AND M.userId=U.id
        ORDER BY M.createdAt ASC
      SQL;
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":chatId", $chatId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($message): array => $this->createMessageRO($message), $messages);
        }

        return [];
    }

    function createMessageRO($message): array
    {
        return [
            "id" => $message["id"],
            "content" => $message["content"],
            "contentType" => $message["contentType"],
            "createdAt" => $message["createdAt"],
            "user" => [
                "id" => $message["user_id"],
                "username" => $message["user_username"],
                "fullname" => $message["user_fullname"],
                "profileImage" => $message["user_profileImage"],
            ]
        ];
    }
}