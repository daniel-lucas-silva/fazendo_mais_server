<?php
namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * Class Logs
 * @package App\Models
 * @property integer $id
 * @property string $username
 * @property string $route
 * @property string $date
 */
class Logs extends Model
{
    public $id;
    public $username;
    public $route;
    public $date;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setConnectionService('db_log'); // Connection service for log database
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return 'logs';
    }

    /**
     * @return array
     */
    public function columnMap()
    {
        return array(
            'id' => 'id',
            'username' => 'username',
            'route' => 'route',
            'date' => 'date',
        );
    }
}