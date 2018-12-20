<?php

class BalanceController extends ControllerBase
{
  public function index($entity_id) {

    $this->initializeGet();

    $rows = 10;
    $order_by = 'created_at desc';
    $offset = 0;
    $limit = $offset + $rows;

    $conditions = 'entity_id = :entity_id:';
    $parameters = ['entity_id' => $entity_id];

    if ($this->request->get('sort') != null && $this->request->get('order') != null) {
      $order_by = $this->request->get('sort') . " " . $this->request->get('order');
    }

    if ($this->request->get('limit') != null) {
      $rows = $this->getQueryLimit($this->request->get('limit'));
      $limit = $rows;
    }

    if ($this->request->get('page') != null) {
      $offset = ($this->request->get('page') - 1) * $limit;
      $limit = $rows;
    }

    if ($this->request->get('filter') != null) {
      $filter = json_decode($this->request->get('filter'), true);
      foreach ($filter as $key => $value) {
        array_push($conditions, $key . " LIKE :" . $key . ":");
        $parameters = $this->array_push_assoc($parameters, $key, "%" . trim($value) . "%");
      }
      $conditions = implode(' AND ', $conditions);
    }

    $balance = EntityBalance::find(
      array(
        $conditions,
        'bind' => $parameters,
        'columns' => '*',
        'order' => $order_by,
        'offset' => $offset,
        'limit' => $limit,
      )
    );

    // Gets total
    $total = EntityBalance::count(
      array(
        $conditions,
        'bind' => $parameters,
      )
    );

    if (!$balance) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'size', $rows);
      $data = $this->array_push_assoc($data, 'total', $total);
      $data = $this->array_push_assoc($data, 'rows', $balance->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function search($entity_id, $text) {
    $this->initializeGet();

    $rows = 10;
    $order_by = "created_at desc";
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

    if ($this->request->get('page') != null) {
      $offset = ($this->request->get('page') - 1) * $limit;
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
              title,
              content,
              created_at,
              MATCH (title, content) AGAINST ('*{$text}*') AS relevance
            FROM entity_balance 
            WHERE entity_id = '{$entity_id}' AND MATCH (title, content) AGAINST ('*{$text}*' IN BOOLEAN MODE) {$conditions}
            ORDER BY relevance DESC, {$order_by} 
            LIMIT {$limit} OFFSET {$offset}";

    $sql_total = "SELECT 
              id, 
              title,
              content,
              created_at,
              MATCH (title, content) AGAINST ('*{$text}*') AS relevance
            FROM entity_balance 
            WHERE entity_id = '{$entity_id}' AND MATCH (title, content) AGAINST ('*{$text}*' IN BOOLEAN MODE) {$conditions}";

    $query = $this->db->query($sql);
    $query_total = $this->db->query($sql_total);

    $query->setFetchMode(Phalcon\Db::FETCH_ASSOC);
    $balance = $query->fetchAll($query);
    $total = $query_total->numRows($query_total);

    if (!$balance) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'size', $rows);
      $data = $this->array_push_assoc($data, 'total', $total);
      $data = $this->array_push_assoc($data, 'rows', $balance);
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function get($id) {

    $this->initializeGet();

    $balance = EntityBalance::findFirst(
      [
        'id = :id:',
        'bind' => ['id' => $id]
      ]
    );
    if (!$balance) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = $balance->toArray();
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function create() {

    $this->initializePost();
    $this->db->begin();

    $rawBody = $this->request->getJsonRawBody(true);

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    if(!($token['user_entity'] || $token['user_level'] == 'Admin')) {
      $this->buildErrorResponse(403, "Proibido!");
    } else {
      $newBalance = new EntityBalance();
      $newBalance->entity_id = $token['user_level'] == 'Admin' ? trim($rawBody["entity_id"]) : $token['user_entity'];

      $columns = ['title', 'content'];

      foreach($columns as $column) {
        if(!empty($rawBody[$column])) {
          $newBalance->$column = trim($rawBody[$column]);
        }
      }

      if (!$newBalance->save()) {
        $this->db->rollback();
        $errors = array();
        foreach ($newBalance->getMessages() as $message) {
          $errors[$message->getField()] = $message->getMessage();
        }
        $this->buildErrorResponse(400, "Não pôde ser criado!", $errors);
      } else {
        $this->db->commit();
        $this->registerLog();
        $data = $newBalance->toArray();
        $this->buildSuccessResponse(201, 'Criado com sucesso!', $data);
      }
    }
  }

  public function update($id) {

    $this->initializePatch();
    $this->db->begin();

    $rawBody = $this->request->getJsonRawBody(true);

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    $balance = EntityBalance::findFirst(
      [
        "id = :id:",
        "bind" => ["id" => $id],
      ]
    );
    if (!$balance) {
      $this->buildErrorResponse(404, "Não encontrado!");
    } else {

      if(!($token['user_entity'] == $balance->entity_id || $token['user_level'] == 'Admin')) {
        $this->buildErrorResponse(403, "Proibido!");
      } else {

        $columns = ['title', 'content'];

        foreach($columns as $column) {
          if(!empty($rawBody[$column])) {
            $balance->$column = trim($rawBody[$column]);
          }
        }

        if (!$balance->save()) {
          $this->db->rollback();
          $errors = array();
          foreach ($balance->getMessages() as $message) {
            $errors[$message->getField()] = $message->getMessage();
          }
          $this->buildErrorResponse(400, "Não pôde ser atualizado!", $errors);
        } else {
          $this->db->commit();
          $this->registerLog();
          $data = $balance->toArray();
          $this->buildSuccessResponse(201, 'Atualizado com sucesso!', $data);
        }
      }
    }
  }

  public function delete($id) {

    $this->initializeDelete();
    $this->db->begin();

    $conditions = 'id = :id:';
    $parameters = array(
      'id' => $id,
    );
    $balance = EntityBalance::findFirst(
      array( $conditions, 'bind' => $parameters )
    );
    if (!$balance) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      if (!$balance->delete()) {
        $this->db->rollback();
        $errors = array();
        foreach ($balance->getMessages() as $message) {
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
