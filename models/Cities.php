<?php

class Cities extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    $this->setConnectionService('db');

    $this->belongsTo(
      'state_id',
      'States',
      'id',
      ['alias' => 'state']
    );
  }

  public function getSource() {
    return 'cities';
  }

  public static function find($parameters = null) {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null) {
    return parent::findFirst($parameters);
  }

}
