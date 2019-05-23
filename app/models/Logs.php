<?php namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * Class Logs
 * @package App\Models
 * @property $id;
 * @property $email;
 * @property $route;
 * @property $date;
 */
class Logs extends Model
{
    public $id;
    public $email;
    public $route;
    public $date;

    public function initialize()
    {
        $this->setConnectionService('db_log'); // Connection service for log database
    }

    public function getSource()
    {
        return 'logs';
    }
}
