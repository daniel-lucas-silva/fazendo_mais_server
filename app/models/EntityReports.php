<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;

class EntityReports extends \Phalcon\Mvc\Model {

  public function initialize() {
    $this->setConnectionService('db');

    $this->belongsTo(
      'entity_id',
      'Entities',
      'id'
    );
  }

  public function validation() {
    $validator = new Validation();

    $validator->add(
      'title',
      new PresenceOf(['message' => 'Por favor, digite um titulo.'])
    );

    $validator->add(
      'content',
      new PresenceOf(['message' => 'Por favor, digite o conteúdo.'])
    );

    return $this->validate($validator);
  }

  public function getSource() {
    return 'entity_reports';
  }

  public static function find($parameters = null) {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null) {
    return parent::findFirst($parameters);
  }

}
