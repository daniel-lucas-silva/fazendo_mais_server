<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;

class Donations extends \Phalcon\Mvc\Model
{

  public function initialize() {
    $this->setConnectionService('db');

    $this->belongsTo( 'entity_id', 'Entities', 'id', ['alias' => 'entity']);
    $this->belongsTo( 'user_id', 'Users', 'id', ['alias' => 'user']);
  }

  public function validation() {
    $validator = new Validation();

    $validator->add(
      'amout',
      new PresenceOf(['message' => 'Por favor, digite uma quantidade.'])
    );

    return $this->validate($validator);
  }

  public function getSource() {
    return 'donations';
  }

  public static function find($parameters = null) {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null) {
    return parent::findFirst($parameters);
  }

}
