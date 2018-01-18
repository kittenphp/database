<?php


namespace kitten\component\database;

use Psr\Log\LoggerInterface;

class DB
{
    /** @var DbPDO */
    protected $pdo;
    /** @var bool  */
    protected $debug = false;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * DB constructor.
     * @param DbPDO $PDO
     * @param bool $debug
     * @param LoggerInterface|null $logger
     */
    public function __construct(DbPDO $PDO,bool $debug = false, LoggerInterface $logger = null)
    {
        $this->pdo = $PDO;
        $this->debug = $debug;
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * @param string $query
     * @param array $args
     * @return array
     */
    public function getOneRow(string $query, array $args = [])
    {
        $this->writeLog($query, $args);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($args);
        $record = $stmt->fetch();
        if ($record===false){
            $error=$this->pdo->errorInfo();
            throw new DBException($error[2]);
        }
        return $record;
    }

    /**
     * @param string $query
     * @param array $args
     * @return array
     */
    public function getMoreRows(string $query, array $args = [])
    {
        $this->writeLog($query, $args);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($args);
        $result= $stmt->fetchAll();
        if ($result===false){
            $error=$this->pdo->errorInfo();
            throw new DBException($error[2]);
        }
        return $result;
    }

    /**
     * @param string $query
     * @param array $args
     * @return mixed
     */
    public function getScalarValue(string $query, array $args = [])
    {
        $this->writeLog($query, $args);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($args);
        $result = $stmt->fetchColumn();
        if ($result===false){
            $error=$this->pdo->errorInfo();
            throw new DBException($error[2]);
        }else{
            return $result;
        }
    }

    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * @param string $sql
     * @param array $args
     * @return int
     */
    public function execute(string $sql,array $args = [])
    {
        $this->writeLog($sql, $args);
        $stmt = $this->pdo->prepare($sql);
        $result= $stmt->execute($args);
        if ($result===false){
            $error=$this->pdo->errorInfo();
            throw new DBException($error[2]);
        }
        $count = $stmt->rowCount();
        return $count;
    }


    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param string $query The sql query with parameter placeholders
     * @param array $params The array of substitution parameters
     * @return string The interpolated query
     */
    public static function interpolateQuery(string $query,array $params)
    {
        //form:  https://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements/1376838#1376838
        $keys = array();
        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
        }
        $query = preg_replace($keys, $params, $query, 1, $count);
        #trigger_error('replaced '.$count.' keys');
        return $query;
    }

    /**
     * @param string $query
     * @param array $params
     */
    protected function writeLog(string $query, array $params = [])
    {
        $logger = $this->logger;
        if ($this->debug && !empty($logger)) {
            $sql = self::interpolateQuery($query, $params);
            $this->logger->info('SQL:{' . $sql . '}');
        }
    }

    public function close(){
        $this->pdo=null;
    }

}