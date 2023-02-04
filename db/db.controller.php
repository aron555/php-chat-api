<?php
  
  @include("./fixtures/users.php");
  @include("./fixtures/chats.php");
  @include("./fixtures/chatParticipants.php");
  @include("./fixtures/messages.php");
  @include_once __DIR__ . "/../config.php";
  
  class DbController
  {
    private PDO $conn;
    
    public function __construct($dbConfig)
    {
      global $config;
      
      $this->connectToDb($dbConfig);
      
      if ($config["mode"] != "test") return;
      
      $this->dropTables(['ChatParticipants', 'Messages', 'Users', 'Chats']);
      // $this->dropDB($dbConfig["name"]);
      
      $this->initialize($dbConfig["name"]);
      
      # Include fixtures from global scope here
      global $usersFixtures, $chatsFixtures, $chatParticipantsFixtures, $messagesFixtures;
      $this->seed('Users', $usersFixtures);
      $this->seed('Chats', $chatsFixtures);
      $this->seed('ChatParticipants', $chatParticipantsFixtures);
      $this->seed('Messages', $messagesFixtures);
    }
    
    private function connectToDb(array $dbConfig)
    {
      try {
        $this->conn = new PDO(
          "{$dbConfig['type']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}",
          $dbConfig['user'],
          $dbConfig['pass']
        );
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (Exception $ex) {
        httpException($ex->getMessage(), 500)['end']();
      }
    }
    
    /**
     *  Drop specified table in DB
     * @param {string[]} $tables Array of tables names
     */
    private function dropTables($tables)
    {
      try {
        foreach ($tables as $table) {
          $sql = "DROP TABLE IF EXISTS $table";
          $this->conn->exec($sql);
        }
      } catch (Exception $ex) {
        httpException("Failed to drop db tables", 500);
      }
    }
  
    private function dropDB($dbName)
    {
      try {
        $sql = "DROP DATABASE IF EXISTS $dbName";
        $this->conn->exec($sql);
      } catch (Exception $ex) {
        httpException("Failed to drop db", 500);
      }
    }
    
    /**
     *  Create all tables with hardcoded sql script
     */
    private function initialize($dbName)
    {
      try {
        # Create Users table
        $users = <<<SQL
        -- CREATE DATABASE IF NOT EXISTS $dbName;
        -- CHARACTER SET utf8mb4;
        -- COLLATE utf8mb4_unicode_ci;
        -- SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
        
        -- USE $dbName;

        CREATE TABLE IF NOT EXISTS Users (
          id VARCHAR(16) PRIMARY KEY,
          username VARCHAR(20) UNIQUE NOT NULL,
          fullname VARCHAR(30) NULL,
          password VARCHAR(30) NOT NULL,
          profileImage VARCHAR(128) NULL,
          description VARCHAR(256) NULL
        );

        CREATE TABLE IF NOT EXISTS Chats (
          id VARCHAR(16) PRIMARY KEY,
          image VARCHAR(128) NULL,
          name VARCHAR(30) NOT NULL,
          isPrivate BOOLEAN NOT NULL DEFAULT TRUE,
          isDeleted BOOLEAN DEFAULT FALSE,
          inviteLink VARCHAR(16) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS ChatParticipants (
          chatId VARCHAR(16) NOT NULL,
          userId VARCHAR(16) NOT NULL,
          permission TINYINT NOT NULL DEFAULT 0,
          lastSeenMessageId VARCHAR(16) DEFAULT 0,
          FOREIGN KEY (chatId) REFERENCES Chats (id),
          FOREIGN KEY (userId) REFERENCES Users (id)
        );

        CREATE TABLE IF NOT EXISTS Messages (
          id VARCHAR(16) PRIMARY KEY,
          chatId VARCHAR(16) NOT NULL,
          userId VARCHAR(16) NOT NULL,
          content NVARCHAR(2048) NOT NULL,
          contentType TINYINT NOT NULL DEFAULT 0,
          createdAt INT NOT NULL,
          FOREIGN KEY (chatId) REFERENCES Chats (id),
          FOREIGN KEY (userId) REFERENCES Users (id)
        );
        
        -- ALTER TABLE ChatParticipants DROP CONSTRAINT unique_chat_user;
        ALTER TABLE ChatParticipants ADD UNIQUE unique_chat_user(chatId, userId);
      SQL;
        $this->conn->exec($users);
      } catch (Exception $ex) {
        httpException("Failed to initialize db", 500);
      }
    }
    
    /**
     *  Seeds data from array in specified table and columns
     * @param {string}   $table    Table name where we want to seed data
     * @param {string[]} $columns  Array of fields names we want to insert
     * @param {object[]} $fixtures Array of fixtures with fields from $fields in same order
     */
    public function seed(string $table, array $fixtures)
    {
      try {
        foreach ($fixtures as $fixture) {
          $fixtureKeys = array_keys($fixture);
          $columnsString = implode(",", $fixtureKeys);
          $fixtureKeysString = ":" . implode(", :", $fixtureKeys);
          
          $sql = "INSERT INTO $table ($columnsString) VALUES ($fixtureKeysString)";
          
          $this->conn->prepare($sql)->execute($fixture);
        }
      } catch (Exception $ex) {
        var_dump($ex);
        httpException("Failed to seed db, table '$table'", 500)['end']();
      }
    }
    
    public function getConnection()
    {
      return $this->conn;
    }
  }
