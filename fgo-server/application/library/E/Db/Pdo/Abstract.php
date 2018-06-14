<?php

/**
 * Pdo 抽象类
 * 
 * @author soft456<soft456@gmail.com>
 * @modify 2016-05-24
 */
abstract class E_Db_Pdo_Abstract extends E_Db_Abstract {

    /**
     * Create a PDO object and connects to the database.
     *
     * @param array $config
     * @return resource
     */
    public function connect() {

        if ($this->config['persistent']) {
            $this->config['options'][PDO::ATTR_PERSISTENT] = true;
        }

        $this->config['options'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $this->config['options'][PDO::ATTR_TIMEOUT] = 1;

        //字符集
        if ($this->config['charset']) {
            $this->config['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '" . $this->config['charset'] . "'";
        }

        for ($n = 0; $n < 2; $n++) {
            try {
                // var_dump($this->_dsn($this->config),$this->config['user'],$this->config['password'],$this->config['options']);
                $this->conn = new PDO($this->_dsn($this->config), $this->config['user'], $this->config['password'], $this->config['options']);
                return $this->conn;
            } catch (PDOException $ex) {
                $this->errors = array('code' => $ex->getCode(), 'msg' => $ex->getMessage());
                //E_Exception_Exception::sendMail($ex->getCode(), 'pdo send:::' . $ex->getTraceAsString());
            } catch (Exception $ex) {
                $this->errors = array('code' => $ex->getCode(), 'msg' => $ex->getMessage());
                //E_Exception_Exception::sendMail($ex->getCode(), 'exception send:::' . $ex->getMessage());
            }
        }
        return NULL;
    }

    /**
     * Select Database
     *
     * @param string $database
     * @return boolean
     */
    public function selectDb($database) {
        return $this->prepareQuery("USE ?;", array($database));
    }

    /**
     * Close mysql connection
     *
     */
    public function close() {
        $this->conn = null;
    }

    /**
     * Free result
     *
     */
    public function free() {
        $this->query = null;
        $this->prepareQuery = null;
    }

    /**
     *  Bind param
     * 
     * @param mixed $parameter
     * @param mixed $value
     * @param string $var_type
     * @return boolean
     */
    protected function _bind($parameter, $value, $var_type = null) {
        if (is_null($var_type)) {
            switch (true) {
                case is_bool($value):
                    $var_type = PDO::PARAM_BOOL;
                    break;
                case is_int($value):
                    $var_type = PDO::PARAM_INT;
                    break;
                case is_null($value):
                    $var_type = PDO::PARAM_NULL;
                    break;
                default:
                    $var_type = PDO::PARAM_STR;
            }
        }
        return $this->stmt->bindValue($parameter, $value, $var_type);
    }

    /**
     * Prepare Query sql
     *
     * @param array $data
     * @return E_Db_Mysql
     */
    protected function _prepareQuery($data = array()) {
        if ($data) {
            $this->stmt->execute($data);
        } else {
            $this->stmt->execute();
        }
        return $this->stmt;
    }

    /**
     * Return the rows affected of the last sql
     *
     * @return int
     */
    public function affectedRows() {
        return $this->query->rowCount();
    }

    /**
     * Get pdo fetch style
     *
     * @param string $style
     * @return int
     */
    protected static function _getFetchStyle($style) {
        switch ($style) {
            case 'ASSOC':
                $style = PDO::FETCH_ASSOC;
                break;
            case 'BOTH':
                $style = PDO::FETCH_BOTH;
                break;
            case 'NUM':
                $style = PDO::FETCH_NUM;
                break;
            case 'OBJECT':
                $style = PDO::FETCH_OBJECT;
                break;
            default:
                $style = PDO::FETCH_ASSOC;
        }

        return $style;
    }

    /**
     * Fetch one row result
     *
     * @param string $type
     * @return mixd
     */
    public function fetch($type = 'ASSOC') {
        for ($n = 0; $n < 2; $n++) {
            try {
                return $this->query->fetch(self::_getFetchStyle(strtoupper($type)));
            } catch (Exception $ex) {
                $this->errors = array('code' => $ex->getCode(), 'msg' => $ex->getMessage());
                $this->db->reConnection($ex->getMessage());
            } finally {
                $this->free();
            }
        }
        return FALSE;
    }

    /**
     * Fetch All result
     *
     * @param string $type
     * @return array
     */
    public function fetchAll($type = 'ASSOC') {
        for ($n = 0; $n < 2; $n++) {
            try {
                return $this->query->fetchAll(self::_getFetchStyle(strtoupper($type)));
            } catch (Exception $ex) {
                $this->errors = array('code' => $ex->getCode(), 'msg' => $ex->getMessage());
                $this->db->reConnection($ex->getMessage());
            } finally {
                $this->free();
            }
        }

        return FALSE;
    }

    /**
     * Initiate a transaction
     *
     * @return boolean
     */
    public function beginTransaction() {
        $this->checkConn();
        return $this->conn->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return boolean
     */
    public function commit() {
        $this->checkConn();
        return $this->conn->commit();
    }

    /**
     * Roll back a transaction
     *
     * @return boolean
     */
    public function rollBack() {
        $this->checkConn();
        return $this->conn->rollBack();
    }

    /**
     * Get the last inserted ID.
     *
     * @param string $tableName
     * @param string $primaryKey
     * @return integer
     */
    public function lastInsertId($tableName = null, $primaryKey = null) {
        $this->checkConn();
        return $this->conn->lastInsertId();
    }

    /**
     * Escape string
     *
     * @param string $str
     * @return string
     */
    public function escape($str) {
        return addslashes($str);
    }

    /**
     * Get error
     *
     * @return array
     */
    public function error() {

        if ($this->conn->errorCode()) {
            $errno = $this->conn->errorCode();
            $error = $this->conn->errorInfo();
        } else {
            $errno = $this->query->errorCode();
            $error = $this->query->errorInfo();
        }

        return array('code' => intval($errno), 'msg' => $error[2]);
    }

    /**
     *  如果数据库连接被断开，重新连接
     */
    public function reConnection($errMsg) {

        if ((FALSE !== strpos($errMsg, 'MySQL server has gone away')) || (FALSE !== strpos($errMsg, 'errno=32 Broken pipe'))) {
            $this->close();
            $this->connect();
        }

        return TRUE;
    }

    /**
     *  检测是否连接了数据库
     */
    public function checkConn() {
        if (is_null($this->conn)) {
            $this->close();
            $this->connect();
        }
    }

}
