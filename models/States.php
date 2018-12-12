<?php

class States extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    $this->hasMany(
      'id',
      'Cities',
      'state_id',
      array('alias' => 'cities')
    );

    $this->setConnectionService('db');
  }

  public function getSource()
  {
    return 'states';
  }

  public static function find($parameters = null)
  {
    return parent::find($parameters);
  }

  public static function findFirst($parameters = null)
  {
    return parent::findFirst($parameters);
  }

}
