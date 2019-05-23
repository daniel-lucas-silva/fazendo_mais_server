<?php

class EntityPerson extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    $this->setConnectionService('db');

    $this->belongsTo(
      'entity_id',
      'Entities',
      'id'
    );

    $this->belongsTo(
      'user_id',
      'Users',
      'id'
    );
  }

  public function getSource() {
    return 'entity_person';
  }

  public static function find($parameters = null) {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null) {
    return parent::findFirst($parameters);
  }
}
