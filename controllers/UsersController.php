<?php

class UsersController extends ControllerBase
{
  public function index()
  {
    $this->initializeGet();

    $rows = 22;
    $order_by = 'username DESC';
    $offset = 0;
    $limit = $offset + $rows;

    if ($this->request->get('sort') != null && $this->request->get('order') != null) {
      $order_by = $this->request->get('sort') . " " . $this->request->get('order');
    }

    if ($this->request->get('limit') != null) {
      $rows = $this->getQueryLimit($this->request->get('limit'));
      $limit = $rows;
    }

    if ($this->request->get('offset') != null) {
      $offset = $this->request->get('offset');
      $limit = $rows;
    }

    $conditions = [];
    $parameters = [];

    if ($this->request->get('filter') != null) {
      $filter = json_decode($this->request->get('filter'), true);
      foreach ($filter as $key => $value) {
        array_push($conditions, $key . " LIKE :" . $key . ":");
        $parameters = $this->array_push_assoc($parameters, $key, "%" . trim($value) . "%");
      }
      $conditions = implode(' AND ', $conditions);
    }


    $users = Users::find(
      [
        $conditions,
        'bind' => $parameters,
        'columns' => 'id, username, email, thumbnail, info, level, entity_id',
        'order' => $order_by,
        'offset' => $offset,
        'limit' => $limit,
      ]
    );

    $total = Users::count(
      [
        $conditions,
        'bind' => $parameters,
      ]
    );

    if (!$users) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'rows_per_page', $rows);
      $data = $this->array_push_assoc($data, 'total_rows', $total);
      $data = $this->array_push_assoc($data, 'rows', $users->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function search($text) {
    $this->initializeGet();

    $rows = 22;
    $order_by = "username desc";
    $offset = 0;
    $limit = $offset + $rows;


    $conditions = [' '];


    if ($this->request->get('sort') != null && $this->request->get('order') != null) {
      $order_by = $this->request->get('sort') . " " . $this->request->get('order');
    }

    if ($this->request->get('limit') != null) {
      $rows = $this->getQueryLimit($this->request->get('limit'));
      $limit = $rows;
    }

    if ($this->request->get('offset') != null) {
      $offset = $this->request->get('offset');
      $limit = $rows;
    }

    if ($this->request->get('filter') != null) {
      $filter = json_decode($this->request->get('filter'), true);
      foreach ($filter as $key => $value) {
        array_push($conditions, "{$key} LIKE '{$value}");
      }
    }

    $conditions = implode(' AND ', $conditions);

    $text = str_replace(' ', '**', trim($text));

    $sql = "SELECT 
              id, 
              username, 
              email, 
              thumbnail, 
              info, 
              level, 
              entity_id,
              MATCH (username, email) AGAINST ('*{$text}*') AS relevance,
              MATCH (username) AGAINST ('*{$text}*') AS title_relevance
            FROM users 
            WHERE MATCH (title, content) AGAINST ('*{$text}*' IN BOOLEAN MODE) {$conditions}
            ORDER BY title_relevance DESC, relevance DESC, {$order_by} 
            LIMIT {$limit} OFFSET {$offset}";

    $query = $this->db->query($sql);
    $query->setFetchMode(Phalcon\Db::FETCH_ASSOC);
    $users = $query->fetchAll($query);
    $total = $query->numRows($query);

    if (!$users) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'rows_per_page', $rows);
      $data = $this->array_push_assoc($data, 'total_rows', $total);
      $data = $this->array_push_assoc($data, 'rows', $users);
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function create() {
    $this->initializePost();

    $this->db->begin();

    $newUser = new Users();
    $newUser->authorized = 1;

    $columns = ['username', 'email', 'password', 'thumbnail', 'info', 'level', 'entity_id'];

    foreach($columns as $column) {
      if(!empty($this->request->getPost($column))) {
        if($column === 'password') {
          $password_hashed = password_hash($this->request->getPost("password"), PASSWORD_BCRYPT);
          $newUser->password = $password_hashed;
        } else {
          $newUser->$column = trim($this->request->getPost($column));
        }
      }
    }

    if (!$newUser->save()) {
      $this->db->rollback();
      $errors = array();

      foreach ($newUser->getMessages() as $message) {
        $errors[$message->getField()] = $message->getMessage();
      }
      $this->buildErrorResponse(400, 'Não pôde ser criado!', $errors);
    } else {
      $this->db->commit();
      $this->registerLog();

      $data = $newUser->toArray();
      $this->buildSuccessResponse(201, 'Criado com sucesso!', $data);
    }
  }

  public function get($id)
  {
    $this->initializeGet();

    $user = Users::findFirst(
      array(
        'id = :id: OR username = :id: OR email = :id:',
        'bind' => [
          'id' => $id
        ]
      )
    );

    if (!$user) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {

      $data = $user->toArray();
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function update($id) {

    $this->initializePatch();
    $this->db->begin();

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    $user = Users::findFirst(
      [
        'id = :id: OR username = :id: OR email = :id:',
        'bind' => [
          'id' => $id
        ],
      ]
    );

    if (!$user) {
      $this->buildErrorResponse(404, "Não encontrado!");
    } else {

      if(!($token['user_username'] == $user->username || $token['level'] == 'Admin')) {
        $this->buildErrorResponse(403, "Proibido!");
      } else {

        $columns = ['username', 'email', 'password', 'thumbnail', 'info', 'entity_id'];

        if($token['user_level'] == 'Admin') {
          $user->level = trim($this->request->getPut('level'));
        }

        foreach($columns as $column) {
          if(!empty($this->request->getPut($column))) {
            if($column === 'password') {
              $password_hashed = password_hash($this->request->getPut("password"), PASSWORD_BCRYPT);
              $user->password = $password_hashed;
            } else {
              $user->$column = trim($this->request->getPut($column));
            }
          }
        }

        if (!$user->save()) {
          $this->db->rollback();
          $errors = array();

          foreach ($user->getMessages() as $message) {
            $errors[$message->getField()] = $message->getMessage();
          }
          $this->buildErrorResponse(400, 'Não pôde ser atualizado!', $errors);
        } else {
          $this->db->commit();
          $this->registerLog();

          $data = $user->toArray();
          $this->buildSuccessResponse(201, 'Criado com sucesso!', $data);
        }
      }
    }
  }

  public function delete($id) {

    $this->initializeDelete();
    $this->db->begin();

    $user = Users::findFirst(
      [
        'id = :id:',
        'bind' => [
          'id' => $id
        ],
      ]
    );

    if (!$user) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {

      if (!$user->delete()) {
        $this->db->rollback();
        $errors = array();
        foreach ($user->getMessages() as $message) {
          $errors[$message->getField()] = $message->getMessage();
        }
        $this->buildErrorResponse(400, 'Não pôde ser deletado!', $errors);
      } else {
        $this->db->commit();
        $this->registerLog();

        $this->buildSuccessResponse(200, 'Deletado com sucesso!');
      }

    }
  }
}
