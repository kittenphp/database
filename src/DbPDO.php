<?php


namespace kitten\component\database;

use PDO;

class DbPDO extends PDO
{
    /**
     * DbPDO constructor.
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public function __construct(string $dsn,string $username,string $password, array $options = [])
    {
        $options= $this->init($options);
        parent::__construct($dsn, $username, $password, $options);
    }

    protected function init(array $options = [])
    {
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        return $options;
    }
}