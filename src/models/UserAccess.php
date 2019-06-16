<?php

namespace App\Models;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
//use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;
//use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Mvc\Model;

/**
 * Class UserAccess
 * @package App\Models
 */
class UserAccess extends Model {

    public $id;
    public $username;
    public $password;
    public $firstname;
    public $lastname;
    public $level;
    public $email;
    public $phone;
    public $mobile;
    public $address;
    public $country;
    public $city;
    public $birthday;
    public $authorised;
    public $block_expires;
    public $login_attempts;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setConnectionService('db');
    }

    /**
     * @return bool
     */
    public function validation()
    {
        $validator = new Validation();
        $validator->add(
            'email',
            new EmailValidator()
        );
        return $this->validate($validator);
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return 'users';
    }

    /**
     * @return array
     */
    public function columnMap()
    {
        return [
            'id' => 'id',
            'username' => 'username',
            'password' => 'password',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'level' => 'level',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'address' => 'address',
            'country' => 'country',
            'city' => 'city',
            'birthday' => 'birthday',
            'authorised' => 'authorised',
            'block_expires' => 'block_expires',
            'login_attempts' => 'login_attempts',
        ];
    }
}