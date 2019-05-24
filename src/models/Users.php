<?php

namespace App\Models;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Mvc\Model;

/**
 * Class User
 * @package App\Models
 * @property integer $id
 */
class Users extends Model {

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
     * Validations and business logic
     *
     * @return boolean
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
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'users';
    }

    /**
     * Independent Column Mapping.
     * Keys are the real names in the table and the values their names in the application
     *
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