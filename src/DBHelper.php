<?php


namespace kitten\component\database;


use Closure;
use InvalidArgumentException;
use kitten\utils\ArrayTool;
use kitten\utils\StringTools;

class DBHelper extends DB
{
    /**
     * @param Closure $closure
     * @throws \Exception
     */
    public function trans(Closure $closure)
    {
        try {
            $this->beginTransaction();
            $closure($this);
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data)
    {
        if (!isset($table) || trim($table) === '') {
            throw new \InvalidArgumentException('Table name can not be empty');
        }
        $twoArray = [];
        if (ArrayTool::isOneArray($data)) {
            $twoArray[] = $data;
        } else {
            $twoArray = $data;
        }
        $rowCount = 0;
        foreach ($twoArray as $item) {
            $count = $this->insertOneRow($table, $item);
            $rowCount = $rowCount + $count;
        }
        return $rowCount;
    }

    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    protected function insertOneRow(string $table, array $data)
    {
        $fieldNames = implode(',', array_keys($data));
        $fieldValues = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($fieldNames) VALUES ($fieldValues);";
        return $this->execute($sql, $data);
    }

    /**
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int
     */
    public function update(string $table, array $data, array $where)
    {
        if (!isset($table) || trim($table) === '') {
            throw new InvalidArgumentException('Table name can not be empty');
        }
        if (empty($data)) {
            throw new InvalidArgumentException('$data argument can not be an empty array');
        }
        if (empty($where)) {
            throw new InvalidArgumentException('$where argument can not be an empty array');
        }
        if (ArrayTool::isMultiArray($data)) {
            throw new InvalidArgumentException('$data: The parameter must be a one-dimensional array');
        }
        if (ArrayTool::isMultiArray($where)) {
            throw new InvalidArgumentException('$where: The parameter must be a one-dimensional array');
        }
        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $fieldDetails .= "$key = ?,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            if ($i == 0) {
                $whereDetails .= "$key = ?";
            } else {
                $whereDetails .= " AND $key = ?";
            }
            $i++;
        }
        $whereDetails = ltrim($whereDetails, ' AND ');
        $sql = "UPDATE $table SET $fieldDetails WHERE $whereDetails;";
        $newArgs = [];
        foreach ($data as $key => $value) {
            $newArgs[] = $value;
        }
        foreach ($where as $key => $value) {
            $newArgs[] = $value;
        }
        return $this->execute($sql, $newArgs);
    }

    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public function updateTableAllRows(string $table, array $data)
    {
        if (!isset($table) || trim($table) === '') {
            throw new InvalidArgumentException('Table name can not be empty');
        }
        if (empty($data)) {
            throw new InvalidArgumentException('$data argument can not be an empty array');
        }
        if (ArrayTool::isMultiArray($data)) {
            throw new InvalidArgumentException('$data: The parameter must be a one-dimensional array');
        }
        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $fieldDetails .= "$key = :$key,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');
        $sql = "UPDATE $table SET $fieldDetails;";
        return $this->execute($sql, $data);
    }

    /**
     * @param string $table
     * @param array $where
     * @param int $limit
     * @return int
     */
    public function delete(string $table, array $where, int $limit = 1)
    {
        if (!isset($table) || trim($table) === '') {
            throw new InvalidArgumentException('Table name can not be empty');
        }
        if (ArrayTool::isMultiArray($where)) {
            throw new InvalidArgumentException('$where: The parameter must be a one-dimensional array');
        }
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            if ($i == 0) {
                $whereDetails .= "$key = :$key";
            } else {
                $whereDetails .= " AND $key = :$key";
            }
            $i++;
        }
        $whereDetails = ltrim($whereDetails, ' AND ');
        $useLimit = '';
        if ($limit > 0) {
            $useLimit = "LIMIT $limit";
        }
        $sql = "DELETE FROM $table WHERE $whereDetails $useLimit;";
        return $this->execute($sql, $where);
    }

    /**
     * @param string $table
     * @param string $primaryKeyName
     * @param string $keyValue
     * @return array
     */
    public function getById(string $table,string $primaryKeyName,string $keyValue)
    {
        if (!isset($table) || trim($table) === '') {
            throw new InvalidArgumentException('Table name can not be empty');
        }
        if (StringTools::isNullOrEmptyString($primaryKeyName)) {
            throw new InvalidArgumentException('primary Key name can not be empty');
        }
        $sql = "select * from {$table} WHERE {$primaryKeyName}=:{$primaryKeyName};";
        $args = [$primaryKeyName => $keyValue];
        return $this->getOneRow($sql, $args);
    }

    /**
     * @param string $table
     * @param array $where
     * @return int
     */
    public function count(string $table, array $where = [])
    {
        if (StringTools::isNullOrEmptyString($table)) {
            throw new InvalidArgumentException('Table name can not be empty');
        }
        if (!empty($where)) {
            $whereDetails = '';
            $i = 0;
            foreach ($where as $key => $value) {
                if ($i == 0) {
                    $whereDetails .= "$key = :$key";
                } else {
                    $whereDetails .= " AND $key = :$key";
                }
                $i++;
            }
            $whereDetails = ltrim($whereDetails, ' AND ');
            $sql = "select count(*) from {$table} WHERE {$whereDetails};";
        } else {
            $sql = "select count(*) from {$table};";
        }
        return (int)$this->getScalarValue($sql, $where);
    }
}