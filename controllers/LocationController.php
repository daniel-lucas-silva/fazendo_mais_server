<?php

class LocationController extends ControllerBase
{

  public function getStates()
  {
    $this->initializeGet();

    $rows = 25;
    $order_by = 'name DESC';
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

    $states = States::find(
      array(
        $conditions,
        'bind' => $parameters,
        'columns' => '*',
        'order' => $order_by,
        'offset' => $offset,
        'limit' => $limit,
      )
    );

    $total = States::count(
      array(
        $conditions,
        'bind' => $parameters,
      )
    );

    if (!$states) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1);
      $data = $this->array_push_assoc($data, 'rows_per_page', $rows);
      $data = $this->array_push_assoc($data, 'total_rows', $total);
      $data = $this->array_push_assoc($data, 'rows', $states->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function getCities()
  {
    $this->initializeGet();

    $tmp_order = [
      'id' => 'Cities.id',
      'name' => 'Cities.name',
      'state' => 'States.name'
    ];

    $rows = 25;
    $order_by = 'Cities.name DESC';
    $offset = 0;
    $limit = $offset + $rows;

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

    if ($this->request->get('state_id') != null) {

      $conditions = 'Cities.state_id = :id:';
      $parameters = array(
        'id' => $this->request->get('state_id'),
      );

    } else {
      $conditions = [];
      $parameters = [];
    }

    if ($this->request->get('filter') != null) {
      $filter = json_decode($this->request->get('filter'), true);
      foreach ($filter as $key => $value) {
        $tmp_conditions = [];
        switch ($key) {
          case 'id':
            $tmp_filter = 'Cities.id';
            break;
          case 'name':
            $tmp_filter = 'Cities.name';
            break;
          case 'state':
            $tmp_filter = 'States.name';
            break;
          case 'state_id':
            $tmp_filter = 'States.id';
            break;
          case 'uf':
            $tmp_filter = 'States.initials';
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

    $cities = Cities::query()
      ->columns([
        'id' => 'Cities.id',
        'name' => 'Cities.name',
        'state' => 'JSON_OBJECT(\'id\', States.id, \'name\', States.name, \'initials\', States.initials)',
      ])
      ->innerJoin('States', 'Cities.state_id = State.id')
      ->where($conditions)
      ->bind($parameters)
      ->orderBy($order_by)
      ->limit($limit, $offset)
      ->execute();

    $total = Cities::count(
      [
        $conditions,
        'bind' => $parameters,
      ]
    );

    if (!$cities) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = [];
      $data = $this->array_push_assoc($data, 'page', ($offset / $rows) + 1);
      $data = $this->array_push_assoc($data, 'rows_per_page', $rows);
      $data = $this->array_push_assoc($data, 'total_rows', $total);
      $data = $this->array_push_assoc($data, 'rows', $cities->toArray());
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

  public function getCity($id)
  {
    $this->initializeGet();

    $conditions = 'Cities.id = :id: OR Cities.name = :id:';
    $parameters = [
      'id' => $id
    ];

    $city = Cities::query()
      ->columns([
        'id' => 'Cities.id',
        'name' => 'Cities.name',
        'state' => 'JSON_OBJECT(\'id\', States.id, \'name\', States.name, \'initials\', States.initials)',
      ])
      ->innerJoin('States', 'Cities.state_id = State.id')
      ->where($conditions)
      ->bind($parameters)
      ->limit(1)
      ->execute();

    if (!$city) {
      $this->buildErrorResponse(404, 'Não encontrado!');
    } else {
      $data = $city->toArray();
      $this->buildSuccessResponse(200, 'Requisiçao completada com sucesso!', $data);
    }
  }

}