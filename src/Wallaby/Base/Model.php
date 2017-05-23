<?php

namespace Wallaby\Base;

abstract class Model
{
    /**
     * @var object PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * Constructor for DB
     * config/db.php store the connection $this->pdo, table $this->tableName
     *
     * @return void
     */
    public function __construct()
    {
        $this->tableName = $this->getTableName();

        $db = require_once ROOT . '/config/db.php';
        $this->pdo = new \PDO($db['dsn'], $db['user'], $db['password'], [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Returns the table name built from controller name with '_'
     * MySuperController => my_super
     *
     * @return string
     */
    protected function getTableName()
    {
        $fullName = get_called_class();

        $array = explode('\\', $fullName);

        $fullName = end($array);

        $regex = '#(?=[A-Z])#';

        return trim(strtolower(preg_replace($regex, '_', $fullName)), '_');
    }
}
