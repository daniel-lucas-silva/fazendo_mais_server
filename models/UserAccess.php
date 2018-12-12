<?php

class UserAccess extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    $this->setConnectionService('db');
  }

  public function getSource()
  {
    return 'users_access';
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
