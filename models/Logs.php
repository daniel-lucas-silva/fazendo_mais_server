<?php

class Logs extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    $this->setConnectionService('db_log'); // Connection service for log database
  }

  public function getSource()
  {
    return 'logs';
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
