<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;

class EntityCategories extends \Phalcon\Mvc\Model
{
  public function initialize() {
    $this->setConnectionService('db');

    $this->hasMany( 'id', 'Entities', 'category_id', ['alias' => 'entities']);
  }

  public function validation() {
    $validator = new Validation();

    $validator->add(
      'title',
      new PresenceOf(['message' => 'Por favor, digite um titulo.'])
    );

    return $this->validate($validator);
  }

  public function getSource() {
    return 'entity_categories';
  }

  public static function find($parameters = null) {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null) {
    return parent::findFirst($parameters);
  }

}
