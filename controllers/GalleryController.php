<?php

class GalleryController extends ControllerBase
{
  public function index() {
    $this->initializeGet();

    $rows = 25;
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
      $limit = $rows;
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

    $galleries = EntityGallery::find(
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
    $total = EntityGallery::count(
      array(
        $conditions,
        'bind' => $parameters,
      )
    );

    if (!$galleries) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1 );
      $data = $this->array_push_assoc($data, 'rows_per_page', $rows);
      $data = $this->array_push_assoc($data, 'total_rows', $total);
      $data = $this->array_push_assoc($data, 'rows', $galleries->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function get($id) {
    $this->initializeGet();

    $conditions = 'id = :id:';
    $parameters = array(
      'id' => $id,
    );
    $gallery = EntityGallery::findFirst(
      array(
        $conditions,
        'bind' => $parameters
      )
    );
    if (!$gallery) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = $gallery->toArray();
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function create() {
    $this->initializePost();
    $this->db->begin();

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    if(!($token['user_entity'] || $token['level'] == 'Admin')) {
      $this->buildErrorResponse(403, "Proibido!");
    } else {
      $newGallery = new EntityGallery();

      $newGallery->entity_id = $token['level'] == 'Admin' ? trim($this->request->getPost("entity_id")) : $token['user_entity'];

      $columns = ['title', 'content'];

      foreach($columns as $column) {
        if(!empty($this->request->getPost($column))) {
          $newGallery->$column = trim($this->request->getPost($column));
        }
      }

      if (!$newGallery->save()) {
        $this->db->rollback();
        $errors = array();
        foreach ($newGallery->getMessages() as $message) {
          $errors[$message->getField()] = $message->getMessage();
        }
        $this->buildErrorResponse(400, "Não pôde ser criado!", $errors);
      } else {
        $this->db->commit();
        $this->registerLog();
        $data = $newGallery->toArray();
        $this->buildSuccessResponse(201, 'Criado com sucesso!', $data);
      }
    }
  }

  public function update($id) {
    $this->initializePatch();
    $this->db->begin();

    $token = $this->getToken() ? (array) $this->decodeToken($this->getToken()) : [];

    $conditions = "id = :id:";
    $parameters = array(
      "id" => $id,
    );
    $gallery = EntityGallery::findFirst(
      array(
        $conditions,
        "bind" => $parameters,
      )
    );
    if (!$gallery) {
      $this->buildErrorResponse(404, "Não encontrado!");
    } else {

      if(!($token['user_entity'] == $gallery->entity_id || $token['level'] == 'Admin')) {
        $this->buildErrorResponse(403, "Proibido!");
      } else {

        $columns = ['title', 'content'];

        foreach($columns as $column) {
          if(!empty($this->request->getPut($column))) {
            $gallery->$column = trim($this->request->getPut($column));
          }
        }

        if (!$gallery->save()) {
          $this->db->rollback();
          $errors = array();
          foreach ($gallery->getMessages() as $message) {
            $errors[$message->getField()] = $message->getMessage();
          }
          $this->buildErrorResponse(400, "Não pôde ser atualizado!", $errors);
        } else {
          $this->db->commit();
          $this->registerLog();
          $data = $gallery->toArray();
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
    $gallery = EntityGallery::findFirst(
      array( $conditions, 'bind' => $parameters )
    );
    if (!$gallery) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      if (!$gallery->delete()) {
        $this->db->rollback();
        $errors = array();
        foreach ($gallery->getMessages() as $message) {
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
