<?php

//use Facebook\Facebook;

class AuthController extends ControllerBase
{

  public function login() {

    $this->initializePost();
    $rawBody = $this->request->getJsonRawBody(true);

    if ( empty($rawBody["identity"]) || empty($rawBody["password"]) ) {
      $this->buildErrorResponse(400, "Dados incompletos.");
    } else {

      $identity = $rawBody["identity"];
      $password = $rawBody["password"];

      $conditions = "email = :identity: OR username = :identity:";
      $parameters = array(
        "identity" => $identity,
      );
      $users = Users::findFirst(
        array(
          $conditions,
          "bind" => $parameters,
        )
      );

      if (!$users) {
        $this->buildErrorResponse(404, "O nome de usuario/email ou senha estão incorretos, por favor tente novamente.");
      } else {

        $block_expires = strtotime($users->block_expires);
        $now = strtotime($this->getNowDateTime());

        if ($block_expires > $now) {
          $this->buildErrorResponse(403, "Muitas tentativas. Tente novamente mais tarde.");
        } else if ($users->authorized == 0) {
          $this->buildErrorResponse(403, "Não autorizado.");
        } else if (!password_verify($password, $users->password)) {

          $users->login_attempts = $users->login_attempts + 1;
          if (!$users->save()) {

            $errors = array();
            foreach ($users->getMessages() as $message) {
              $errors[$message->getField()] = $message->getMessage();
            }
            $this->buildErrorResponse(400, "Algo errado!", $errors);
          } else {
            if ($users->login_attempts <= 4) {
              $this->buildErrorResponse(400, "Senha incorreta!");
            } else {

              $block_date = $this->getNowDateTimePlusMinutes(120); // 2 min
              $users->block_expires = $block_date;

              if (!$users->save()) {
                // Send errors
                $errors = array();
                foreach ($users->getMessages() as $message) {
                  $errors[$message->getField()] = $message->getMessage();
                }
                $this->buildErrorResponse(400, "Algo errado!", $errors);
              } else {
                $this->buildErrorResponse(400, "Muitas tentativas. Tente novamente mais tarde");
              }
            }
          }
        } else {

          $options = [
            'cost' => 10, // the default cost is 10, max is 12.
          ];

          if (password_needs_rehash($users->password, PASSWORD_DEFAULT, $options)) {

            $newHash = password_hash($password, PASSWORD_DEFAULT, $options);

            $users->password = $newHash;
            if (!$users->save()) {

              $errors = array();
              foreach ($users->getMessages() as $message) {
                $errors[$message->getField()] = $message->getMessage();
              }
              $this->buildErrorResponse(400, "Algo errado!", $errors);
            }
          }

          $users_data = array(
            "id" => $users->id,
            "email" => $users->email,
            "username" => $users->username,
            "thumbnail" => $users->thumbnail,
            "level" => $users->level,
            "entity" => $users->entity_id == 'pending' ? 'pending' : $users->getEntity(['columns' => 'id, name, thumbnail'])
          );

          $iat = strtotime($this->getNowDateTime());
          $exp = strtotime("+" . $this->tokenConfig['expiration_time'] . " seconds", $iat);

          $token_data = array(
            "iss" => $this->tokenConfig['iss'],
            "aud" => $this->tokenConfig['aud'],
            "iat" => $iat,
            "exp" => $exp,
            "user_username" => $users->username,
            "user_email" => $users->email,
            "user_level" => $users->level,
            "user_entity" => $users->entity_id,
            "rand" => rand() . microtime(),
          );

          $token = $this->encodeToken($token_data);

          $data = array(
            "token" => $token,
            "user" => $users_data,
          );

          $users->login_attempts = 0;

          if (!$users->save()) {

            $errors = array();
            foreach ($users->getMessages() as $message) {
              $errors[$message->getField()] = $message->getMessage();
            }
            $this->buildErrorResponse(400, "Algo errado!", $errors);
          } else {

            $userAccess = new UserAccess();
            $userAccess->email = $users->email;
            if (isset($headers["Http-Client-Ip"]) || !empty($headers["Http-Client-Ip"])) {
              $userAccess->ip = $headers["Http-Client-Ip"];
            } else {
              $userAccess->ip = $this->request->getClientAddress();
            }

            $userAccess->navegador = $this->request->getUserAgent();
            $userAccess->data = $this->getNowDateTime();
            if (!$userAccess->save()) {
              $errors = array();
              foreach ($userAccess->getMessages() as $message) {
                $errors[$message->getField()] = $message->getMessage();
              }
              $this->buildErrorResponse(400, "Algo errado!", $errors);
            } else {
              $this->buildSuccessResponse(200, "Requisiçao completada com sucesso!", $data);
            }
          }
        }
      }
    }

  }

  public function register() {
    $this->initializePost();
    $rawBody = $this->request->getJsonRawBody(true);

    $this->db->begin();

    $newUser = new Users();
    $newUser->id          = uniqid('__u');
    $newUser->email       = trim($rawBody["email"]);
    $newUser->username    = trim($rawBody["username"]);
    $newUser->level       = trim($rawBody["is_entity"]) == true ? 'Entity' : 'Donor';
    $newUser->entity_id   = trim($rawBody["is_entity"]) == true ? 'pending' : null;
    $newUser->authorized  = 1;

    $password_hashed = password_hash($rawBody["password"], PASSWORD_BCRYPT);
    $newUser->password = $password_hashed;

    if (!$newUser->save()) {
      $this->db->rollback();
      $errors = array();
      foreach ($newUser->getMessages() as $message) {
        $errors[$message->getField()] = $message->getMessage();
      }
      $this->buildErrorResponse(400, "Não pode ser criado.", $errors);
    } else {
      $this->db->commit();

      $newUser_data = array(
        "id"        => $newUser->id,
        "email"     => $newUser->email,
        "username"  => $newUser->username,
        "thumbnail" => NULL,
        "level"     => $newUser->level,
        "entity"    => $newUser->entity_id
      );

      $iat = strtotime($this->getNowDateTime());
      $exp = strtotime("+" . $this->tokenConfig['expiration_time'] . " seconds", $iat);

      $token_data = array(
        "iss" => $this->tokenConfig['iss'],
        "aud" => $this->tokenConfig['aud'],
        "iat" => $iat,
        "exp" => $exp,
        "user_id" => $newUser->id,
        "user_email" => $newUser->email,
        "user_username" => $newUser->username,
        "user_level" => $newUser->level,
        "user_entity" => $newUser->entity_id,
        "rand" => rand() . microtime(),
      );

      $token = $this->encodeToken($token_data);

      $data = array(
        "token" => $token,
        "user" => $newUser_data,
      );

      $userAccess = new UserAccess();
      $userAccess->email = $newUser->email;
      if (isset($headers["Http-Client-Ip"]) || !empty($headers["Http-Client-Ip"])) {
        $userAccess->ip = $headers["Http-Client-Ip"];
      } else {
        $userAccess->ip = $this->request->getClientAddress();
      }

      $userAccess->browser = $this->request->getUserAgent();
      $userAccess->date = $this->getNowDateTime();
      if (!$userAccess->save()) {
        // Send errors
        $errors = array();
        foreach ($userAccess->getMessages() as $message) {
          $errors[$message->getField()] = $message->getMessage();
        }
        $this->buildErrorResponse(400, "Algo errado!", $errors);
      } else {
        //return 200, ALL OK LOGGED IN
        $this->buildSuccessResponse(200, "Requisiçao completada com sucesso!", $data);
      }
    }
  }

  public function facebook() {

  }

  public function changePassword() {

    $this->initializePatch();
    $rawBody = $this->request->getJsonRawBody(true);

    $this->db->begin();

    if (empty($rawBody["currentPassword"]) || empty($rawBody["newPassword"])) {
      $this->buildErrorResponse(400, "Dados incompletos!");
    } else {

      $token = $this->getToken() ? (array)$this->decodeToken($this->getToken()) : [];

      $conditions = "email = :email:";
      $parameters = array(
        'email' => $token['user_email'],
      );
      $users = Users::findFirst(
        array(
          $conditions,
          "bind" => $parameters,
        )
      );

      if (!$users) {
        $this->buildErrorResponse(404, "Usuário não encontrado.");
      } else {
        $currentPassword = $rawBody["currentPassword"];
        $newPassword = $rawBody["newPassword"];

        if (!password_verify($currentPassword, $users->password)) {
          $this->buildErrorResponse(400, "Senha incorreta!");
        } else {
          $options = [
            'cost' => 10,
          ];
          $newHash = password_hash($newPassword, PASSWORD_DEFAULT, $options);
          $users->password = $newHash;

          if (!$users->save()) {
            // Send errors
            $errors = array();
            foreach ($users->getMessages() as $message) {
              $errors[$message->getField()] = $message->getMessage();
            }
            $this->buildErrorResponse(400, "Algo errado", $errors);
          } else {
            $this->buildSuccessResponse(200, "Requisiçao completada com sucesso!", "");
          }
        }
      }
    }
  }

  public function me() {

    $this->initializeGet();

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    $conditions = "email = :email:";
    $parameters = array(
      'email' => $token['user_email'],
    );
    $users = Users::findFirst(
      array(
        $conditions,
        "bind" => $parameters,
      )
    );

    if (!$users) {
      $this->buildErrorResponse(404, "Usuário não encontrado.");
    } else {

      $users_data = array(
        "id"        => $users->id,
        "email"     => $users->email,
        "username"  => $users->username,
        "thumbnail" => $users->thumbnail,
        "level"     => $users->level,
        "entity"    => $users->entity_id == 'pending' ? 'pending' : $users->getEntity(['columns' => 'id, name, thumbnail'])
      );

      $iat = strtotime($this->getNowDateTime());
      $exp = strtotime("+" . $this->tokenConfig['expiration_time'] . " seconds", $iat);

      $token_data = array(
        "iss" => $this->tokenConfig['iss'],
        "aud" => $this->tokenConfig['aud'],
        "iat" => $iat,
        "exp" => $exp,
        "user_username" => $users->username,
        "user_email" => $users->email,
        "user_level" => $users->level,
        "user_entity" => $users->entity_id,
        "rand" => rand() . microtime(),
      );

      $token = $this->encodeToken($token_data);

      $data = array(
        "token" => $token,
        "user" => $users_data,
      );

      $this->buildSuccessResponse(200, "Requisiçao completada com sucesso!", $data);
    }
  }

}
