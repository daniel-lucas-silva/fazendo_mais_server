<?php

class UserFollow extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    $this->setConnectionService('db');

    //TODO: many to many

  }

  public function getSource()
  {
    return 'user_follow';
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
