<?php namespace App\Models;

use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;
use Phalcon\Validation\Validator\PresenceOf;

class Users extends Model
{

  public function initialize()
  {

    $this->hasOne(
      'entity_id',
      'Entities',
      'id',
      array('alias' => 'entity')
    );

    $this->setConnectionService('db');
  }

  public function validation()
  {
    $validator = new Validation();

    $validator->add(
      'username',
      new PresenceOf(['message' => 'Por favor, digite o \'nome de usuário\'.'])
    );

    $validator->add(
      'username',
      new UniquenessValidator(['message' => 'Este \'nome de usuário\' já está em uso.'])
    );

    $validator->add(
      'password',
      new PresenceOf(['message' => 'Por favor, digite uma senha.'])
    );

    $validator->add(
      'email',
      new PresenceOf(['message' => 'Por favor, digite um e-mail.'])
    );

    $validator->add(
      'email',
      new EmailValidator(['message' => 'Por favor, digite um e-mail válido.'])
    );

    $validator->add(
      'email',
      new UniquenessValidator(['message' => 'Este e-mail já está em uso.'])
    );

    return $this->validate($validator);
  }

  public function getSource()
  {
    return 'users';
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
