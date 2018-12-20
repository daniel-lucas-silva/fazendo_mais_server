<?php

class CategoriesController extends ControllerBase
{
  public function index() {
    $this->initializeGet();

    $rows = 10;
    $order_by = 'id desc';
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
    }

    if ($this->request->get('page') != null) {
      $offset = ($this->request->get('page') - 1) * $limit;
    }

    if ($this->request->get('id') != null) {

      $conditions = 'entity_id = :entity_id:';
      $parameters = array(
        'entity_id' => $this->request->get('id'),
      );
    } else {
      $conditions = [];
      $parameters = [];
    }

    if ($this->request->get('filter') != null) {
      $filter = json_decode($this->request->get('filter'), true);
      foreach ($filter as $key => $value) {
        array_push($conditions, $key . " LIKE :" . $key . ":");
        $parameters = $this->array_push_assoc($parameters, $key, "%" . trim($value) . "%");
      }
      $conditions = implode(' AND ', $conditions);
    }

    $categories = EntityCategories::find(
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
    $total = EntityCategories::count(
      array(
        $conditions,
        'bind' => $parameters,
      )
    );

    if (!$categories) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'size', $rows);
      $data = $this->array_push_assoc($data, 'total', $total);
      $data = $this->array_push_assoc($data, 'rows', $categories->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function search($text) {
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

    if ($this->request->get('offset') != null) {
      $offset = $this->request->get('offset');
    }

    if ($this->request->get('page') != null) {
      $offset = ($this->request->get('page') - 1) * $limit;
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
              slug, 
              title,
              description,
              thumbnail,
              created_at,
              MATCH (title, description) AGAINST ('*{$text}*') AS relevance
            FROM entity_categories 
            WHERE MATCH (title, description) AGAINST ('*{$text}*' IN BOOLEAN MODE) {$conditions}
            ORDER BY relevance DESC, {$order_by} 
            LIMIT {$limit} OFFSET {$offset}";

    $sql_total = "SELECT 
              id, 
              slug, 
              title,
              description,
              thumbnail,
              created_at,
              MATCH (title, description) AGAINST ('*{$text}*') AS relevance
            FROM entity_categories 
            WHERE MATCH (title, description) AGAINST ('*{$text}*' IN BOOLEAN MODE) {$conditions}";

    $query = $this->db->query($sql);
    $query_total = $this->db->query($sql_total);

    $query->setFetchMode(Phalcon\Db::FETCH_ASSOC);
    $categories = $query->fetchAll($query);
    $total = $query_total->numRows($query_total);

    if (!$categories) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'size', $rows);
      $data = $this->array_push_assoc($data, 'total', $total);
      $data = $this->array_push_assoc($data, 'rows', $categories);
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function get($id) {
    $this->initializeGet();

    $category = EntityCategories::findFirst(
      [
        "id = :id:",
        "bind" => [
          "id" => $id
        ]
      ]
    );

    if (!$category) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = $category->toArray();
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function create() {
    $this->initializePost();
    $this->db->begin();

    $rawBody = $this->request->getJsonRawBody(true);

    $newCategory = new EntityCategories();

    $columns = ['title', 'description'];

    foreach($columns as $column) {
      if(!empty($rawBody[$column])) {
        $newCategory->$column = trim($rawBody[$column]);
      }
    }

    $newCategory->slug = $this->genSlug($rawBody['title']);

    if (!$newCategory->save()) {
      $this->db->rollback();
      $errors = [];
      foreach ($newCategory->getMessages() as $message) {
        $errors[$message->getField()] = $message->getMessage();
      }
      $this->buildErrorResponse(400, "Não pôde ser criado!", $errors);
    } else {
      $this->db->commit();
      $this->registerLog();
      $data = $newCategory->toArray();
      $this->buildSuccessResponse(201, 'Criado com sucesso!', $data);
    }
  }

  public function update($id) {
    $this->initializePatch();
    $this->db->begin();

    $rawBody = $this->request->getJsonRawBody(true);

    $category = EntityCategories::findFirst(
      [
        "id = :id:",
        "bind" => [
          "id" => $id
        ],
      ]
    );
    if (!$category) {
      $this->buildErrorResponse(404, "Não encontrado!");
    } else {

      $columns = ['title', 'description'];

      foreach($columns as $column) {
        if(!empty($rawBody[$column])) {
          $category->$column = trim($rawBody[$column]);
        }
      }

      $category->slug = $this->genSlug($rawBody['title']);

      if (!$category->save()) {
        $this->db->rollback();
        $errors = [];
        foreach ($category->getMessages() as $message) {
          $errors[$message->getField()] = $message->getMessage();
        }
        $this->buildErrorResponse(400, "Não pôde ser atualizado!", $errors);
      } else {
        $this->db->commit();
        $this->registerLog();
        $data = $category->toArray();
        $this->buildSuccessResponse(201, 'Atualizado com sucesso!', $data);
      }
    }
  }

  public function delete($id) {
    $this->initializeDelete();
    $this->db->begin();

    $category = EntityCategories::findFirst(
      [
        'id = :id:',
        'bind' => [
          'id' => $id
        ]
      ]
    );

    if (!$category) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      if (!$category->delete()) {
        $this->db->rollback();
        $errors = [];
        foreach ($category->getMessages() as $message) {
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
