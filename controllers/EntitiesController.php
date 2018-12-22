<?php

class EntitiesController extends ControllerBase
{
  public function index() {
    $this->initializeGet();

    $rows = 22;
    $order_by = 'Entities.name DESC';
    $offset = 0;
    $limit = $offset + $rows;

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

    $conditions = [];
    $parameters = [];

    if ($this->request->get('filter') != null) {
      $filter = json_decode($this->request->get('filter'), true);
      foreach ($filter as $key => $value) {
        $tmp_conditions = [];
        switch ($key) {
          case 'id':
            $tmp_filter = 'Entities.id';
            break;
          case 'slug':
            $tmp_filter = 'Entities.slug';
            break;
          case 'name':
            $tmp_filter = 'Entities.name';
            break;
          case 'about':
            $tmp_filter = 'Entities.about';
            break;
          case 'city':
            $tmp_filter = 'Cities.name';
            break;
          case 'state':
            $tmp_filter = 'States.initials';
            break;
          case 'category':
            $tmp_filter = 'EntityCategories.id';
            break;
          default:
            $tmp_filter = $key;
            break;
        }
        $tmp_filter = explode(' OR ', $tmp_filter);
        foreach ($tmp_filter as $filter_value) {
          array_push($tmp_conditions, $filter_value . " LIKE :" . str_replace(".", "_", $key) . ":");
          $parameters = $this->array_push_assoc($parameters, str_replace(".", "_", $key), "%" . trim($value) . "%");
        }
        $tmp_conditions = implode(' OR ', $tmp_conditions);
        array_push($conditions, "(" . $tmp_conditions . ")");
      }
    }
    $conditions = implode(' AND ', $conditions);

    $entities = Entities::query()
      ->columns([
        'id'          => 'Entities.id',
        'slug'        => 'Entities.slug',
        'name'        => 'Entities.name',
        'about'       => 'Entities.about',
        'thumbnail'   => 'Entities.thumbnail',
        'info'        => 'Entities.info',
        'category'    => 'JSON_OBJECT(\'id\', EntityCategories.id, \'title\', EntityCategories.title, \'slug\', EntityCategories.slug)',
        'location'    => 'JSON_OBJECT(\'id\', Cities.id, \'city\', Cities.name, \'state\', States.initials)',
        'created_at'  => 'Entities.created_at'
      ])
      ->leftJoin('EntityCategories', 'Entities.category_id = EntityCategories.id')
      ->leftJoin('Cities', 'Entities.city_id = Cities.id')
      ->leftJoin('States', 'Cities.state_id = States.id')
      ->where($conditions)
      ->bind($parameters)
      ->orderBy($order_by)
      ->limit($limit, $offset)
      ->execute();

    $total = Entities::count(
      array(
        $conditions,
        'bind' => $parameters,
      )
    );

    if (!$entities) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'size', $rows);
      $data = $this->array_push_assoc($data, 'total', $total);
      $data = $this->array_push_assoc($data, 'rows', $entities->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function search($text) {
    $this->initializeGet();

    $tmp_order = [
      'name'       => 'entities.name',
      'created_at' => 'entities.created_at'
    ];

    $rows = 22;
    $order_by = "{$tmp_order['name']} desc";
    $offset = 0;
    $limit = $offset + $rows;

    $conditions = [' '];

    if ($this->request->get('sort') != null && $this->request->get('order') != null) {
      $order_by = $tmp_order[$this->request->get('sort')] . " " . $this->request->get('order');
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
        $tmp_conditions = [];
        switch ($key) {
          case 'id':
            $tmp_filter = 'entities.id';
            break;
          case 'slug':
            $tmp_filter = 'entities.slug';
            break;
          case 'name':
            $tmp_filter = 'entities.name';
            break;
          case 'about':
            $tmp_filter = 'entities.about';
            break;
          case 'city':
            $tmp_filter = 'cities.name';
            break;
          case 'state':
            $tmp_filter = 'states.initials';
            break;
          case 'category':
            $tmp_filter = 'entity_categories.id';
            break;
          default:
            $tmp_filter = $key;
            break;
        }
        $tmp_filter = explode(' OR ', $tmp_filter);
        foreach ($tmp_filter as $filter_value) {
          array_push($tmp_conditions, $filter_value . " LIKE '{$value}'");
        }
        $tmp_conditions = implode(' OR ', $tmp_conditions);
        array_push($conditions, "(" . $tmp_conditions . ")");
      }
    }
    $conditions = implode(' AND ', $conditions);

    $text = str_replace(' ', '**', trim($text));

    $sql = "SELECT 
        entities.id AS id, 
        entities.slug AS slug, 
        entities.name AS name, 
        entities.about AS about, 
        entities.thumbnail AS thumbnail, 
        entities.created_at AS created_at,
        JSON_OBJECT('id', entity_categories.id, 'title', entity_categories.title, 'slug', entity_categories.slug) AS category,
        JSON_OBJECT('id', cities.id, 'city', cities.name, 'state', states.initials) AS location,
        MATCH (entities.name, entities.about) AGAINST ('*{$text}*') AS relevance
      FROM entities 
      LEFT JOIN entity_categories ON entities.category_id = entity_categories.id
      LEFT JOIN cities ON entities.city_id = cities.id
      LEFT JOIN states ON cities.state_id = states.id
      WHERE MATCH (entities.name, entities.about) AGAINST ('*{$text}*' IN BOOLEAN MODE) {$conditions}
      ORDER BY relevance DESC, {$order_by} 
      LIMIT {$limit} OFFSET {$offset}";

    $query = $this->db->query($sql);
    $query->setFetchMode(Phalcon\Db::FETCH_ASSOC);
    $entities = $query->fetchAll($query);
    $total = $query->numRows($query);

    if (!$entities) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'size', $rows);
      $data = $this->array_push_assoc($data, 'total', $total);
      $data = $this->array_push_assoc($data, 'rows', $entities);
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function create() {
    $this->initializePost();
    $this->db->begin();

    $rawBody = $this->request->getJsonRawBody(true);

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    $user = Users::findFirst(
      array(
        'email = :email:',
        "bind" => [
          'email' => $token['user_email']
        ],
      )
    );

    if(!$user) {
      $this->buildErrorResponse(404, "Usuário não encontrado.");
    }
    else {

      if($user->entity_id !== 'pending') {
        $this->buildErrorResponse(403, "Proibido!");
      }
      else {

        $newEntity = new Entities();
        $newEntity->id = uniqid('__e');

        $columns = ['name', 'about', 'info', 'city_id', 'category_id'];

        foreach($columns as $column) {
          if(!empty($rawBody[$column])) {
            $newEntity->$column = trim($rawBody[$column]);
          }
        }

        $slug = $this->genSlug(substr(uniqid(),6,5)." ".$rawBody['name']);

        if(!empty($rawBody['thumbnail'])) {
          $newEntity->thumbnail = $this->genImage($slug, trim($rawBody['thumbnail']));
        }

        $newEntity->slug = $slug;

        if (!$newEntity->save()) {
          $this->db->rollback();
          $errors = array();
          foreach ($newEntity->getMessages() as $message) {
            $errors[$message->getField()] = $message->getMessage();
          }
          $this->buildErrorResponse(400, "Não pôde ser criado!", $errors);
        } else {

          $user->entity_id = $newEntity->id;
          $user->save();

          $this->db->commit();
          $this->registerLog();
          $data = $newEntity->toArray();
          $this->buildSuccessResponse(201, 'Criado com sucesso!', $data);

        }
      }
    }
  }

  public function get($id)
  {
    $this->initializeGet();

    $conditions = 'Entities.id = :id: OR Entities.slug = :id:';
    $parameters = array(
      'id' => $id,
    );

    $entity = Entities::query()
      ->columns([
        'id'          => 'Entities.id',
        'slug'        => 'Entities.slug',
        'name'        => 'Entities.name',
        'about'       => 'Entities.about',
        'thumbnail'   => 'Entities.thumbnail',
        'info'        => 'Entities.info',
        'category'    => 'JSON_OBJECT(\'id\', EntityCategories.id, \'title\', EntityCategories.title, \'slug\', EntityCategories.slug)',
        'location'    => 'JSON_OBJECT(\'id\', Cities.id, \'city\', Cities.name, \'state\', States.initials)',
        'created_at'  => 'Entities.created_at'
      ])
      ->leftJoin('EntityCategories', 'Entities.category_id = EntityCategories.id')
      ->leftJoin('Cities', 'Entities.city_id = Cities.id')
      ->leftJoin('States', 'Cities.state_id = States.id')
      ->where($conditions)
      ->bind($parameters)
      ->limit(1)
      ->execute();

    if (!$entity) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = $entity->toArray();
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function update($id)
  {
    $this->initializePatch();
    $this->db->begin();

    $rawBody = $this->request->getJsonRawBody(true);

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    $entity = Entities::findFirst(
      array(
        "id = :id: OR slug = :id:",
        "bind" => [
          "id" => $id
        ],
      )
    );

    if (!$entity) {
      $this->buildErrorResponse(404, "Não encontrado!");
    } else {

      if(!($token['user_entity'] == $entity->id || $token['level'] == 'Admin')) {
        $this->buildErrorResponse(403, "Proibido!");
      } else {

        $columns = ['name', 'about', 'info', 'city_id', 'category_id'];

        foreach($columns as $column) {
          if(!empty($rawBody[$column])) {
            $entity->$column = trim($rawBody[$column]);
          }
        }

        if(!empty($rawBody['name'])) {
          $slug = $this->genSlug(substr(uniqid(),6,5)." ".$rawBody['name']);
          $entity->slug = $slug;
        }

        if(!empty($rawBody['thumbnail'])) {
          $entity->thumbnail = $this->genImage($slug, trim($rawBody['thumbnail']));
        }

        if (!$entity->save()) {
          $this->db->rollback();

          $errors = array();
          foreach ($entity->getMessages() as $message) {
            $errors[$message->getField()] = $message->getMessage();
          }

          $this->buildErrorResponse(400, "Não pôde ser atualizado!", $errors);

        } else {
          $data = $entity->toArray();

          $this->db->commit();
          $this->registerLog();

          $this->buildSuccessResponse(200, "Atualizado com sucesso!", $data);
        }
      }
    }
  }

  public function delete($id)
  {

    $this->initializeDelete();

    $this->db->begin();

    $entity = Entities::findFirst(
      [
        'id = :id: OR slug = :id:',
        'bind' => [
          'id' => $id
        ],
      ]
    );

    if (!$entity) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      if (!$entity->delete()) {
        $this->db->rollback();
        $errors = array();
        foreach ($entity->getMessages() as $message) {
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
