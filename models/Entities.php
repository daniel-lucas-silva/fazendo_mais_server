<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;

class Entities extends \Phalcon\Mvc\Model
{

  public function initialize()
  {
    /**
     * PERMITE VALORES NULOS
     */

    $this->hasManyToMany(
      'id',
      'UserFollow',
      'entity_id',
      'user_id',
      'UserInfo',
      'user_id',
      array('alias' => 'folowers')
    );

    $this->setConnectionService('db');

    $this->belongsTo(
      'category_id',
      'EntityCategories',
      'id',
      array('alias' => 'category')
    );

    $this->hasOne(
      'city_id',
      'Cities',
      'id',
      [ 'alias' => 'city' ]
    );

  }

  public function getSource()
  {
    return 'entities';
  }

  public function validation()
  {
    $validator = new Validation();

    $validator->add(
      'name',
      new PresenceOf(['message' => 'Por favor, digite o nome da entidade.'])
    );

    $validator->add(
      'about',
      new PresenceOf(['message' => 'Por favor, digite algo sobre a entidade.'])
    );

    return $this->validate($validator);
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
