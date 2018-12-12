<?php

class EntityGallery extends \Phalcon\Mvc\Model
{

  public function initialize() {
    $this->setConnectionService('db');

    $this->belongsTo( 'entity_id', 'Entities', 'id', ['alias' => 'entity']);
  }

  public function getSource() {
    return 'entity_gallery';
  }

  public static function find($parameters = null) {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null) {
    return parent::findFirst($parameters);
  }

}
