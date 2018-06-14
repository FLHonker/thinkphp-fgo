<?php

/**
 * db 抽象类
 * 
 * @author soft456<soft456@gmail.com>
 */
abstract class E_Db_Abstract {

    /**
     * Configuration
     *
     * @var array
     */
    public $config = array(
        'adapter' => 'Pdo_Mysql',
        'charset' => 'utf8',
        'persistent' => true,
        'options' => array()
    );

    /**
     * Connection
     *
     * @var resource
     */
    public $conn = null;

    /**
     * Query handler
     *
     * @var resource
     */
    public $query = null;

    /**
     * PDOStatement
     *
     * @var resource
     */
    public $stmt = null;

    /**
     * Debug or not
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * Log
     *
     * @var array
     */
    public $log = array();

    /**
     *
     * @var array 
     */
    protected $_nullRs = array('is null', 'is not null');

    /**
     * 最后执行的SQL语句
     * 
     * @var string 
     */
    public $lastSql = null;

    /**
     * 最后执行SQL语句的绑定数据
     * 
     * @var array 
     */
    public $lastData = null;

    /**
     * Error infomation
     *
     * @var array
     */
    public $errors = array();

    /**
     * Constructor.
     *
     * $config is an array of key/value pairs
     * containing configuration options.  These options are common to most adapters:
     *
     * host           => (string) What host to connect to, defaults to localhost
     * user           => (string) Connect to the database as this username.
     * password       => (string) Password associated with the username.
     * database       => (string) The name of the database to user
     *
     * Some options are used on a case-by-case basis by adapters:
     *
     * port           => (string) The port of the database
     * persistent     => (boolean) Whether to use a persistent connection or not, defaults to false
     * charset        => (string) The charset of the database
     *
     * @param  array $config
     */
    public function __construct($config) {
        $this->config = $config + $this->config;
    }

    /**
     *  Prepare Query Sql
     * 
     * @param string $sql
     * @param mixed $data string or array
     * @return mixed 
     */
    public function prepareQuery($sql, $data) {

        $this->checkConn();

        for ($n = 0; $n < 2; $n++) {
            try {
                $this->stmt = $this->conn->prepare($sql);
                break;
            } catch (exception $e) {
                $this->errors = array('code' => $ex->getCode(), 'msg' => $ex->getMessage());
                $this->reConnection($e->getMessage());
            }
        }

        is_array($data) || ($data = array($data));

        $this->lastSql = $sql;
        $this->lastData = $data;

        //Bind param
        foreach ($data as $key => $value) {
            $this->_bind($key + 1, $value);
        }

        if ($this->query = $this->_prepareQuery()) {
            return $this->query;
        }
    }

    /**
     * Get SQL result
     *
     * @param string $sql
     * @param string $type
     * @return mixed
     */
    public function prepareSql($sql, $data, $type = 'ASSOC') {

        $this->prepareQuery($sql, $data);

        $tags = explode(' ', $sql, 2);
        switch (strtoupper($tags[0])) {
            case 'SELECT':
                ($result = $this->fetchAll($type)) || ($result = array());
                break;
            case 'INSERT':
                $result = $this->lastInsertId();
                break;
            case 'UPDATE':
            case 'DELETE':
                $result = $this->affectedRows();
                break;
            default:
                $result = $this->query;
        }

        return $result;
    }

    /**
     * Get a result row
     *
     * @param string $sql
     * @param string $type
     * @return array
     */
    public function prepareRow($sql, $data, $type = PDO::FETCH_ASSOC) {
        $this->prepareQuery($sql, $data);
        return $this->fetch($type);
    }

    /**
     * Get first column of result
     *
     * @param string $sql
     * @return string
     */
    public function prepareCol($sql, $data) {
        $this->prepareQuery($sql, $data);
        $result = $this->fetch(PDO::FETCH_ASSOC);
        return empty($result) ? null : current($result);
    }

    /**
     * Find data
     *
     * @param array $opts
     * @param string $table 
     * @return array
     */
    public function prepareFind($opts, $table) {
        if (is_string($opts)) {
            $opts = array('where' => $opts);
        }

        $opts += array(
            'fields' => '*',
            'where' => array(),
            'order' => null,
            'group' => null,
            'page' => -1,
            'perpage' => -1
        );

        //强制转换
        $opts['page'] = intval($opts['page']);
        $opts['perpage'] = intval($opts['perpage']);

        $whereSql = NULL;
        $dataDb = array();

        if ($opts['where']) {
            $whereRs = $this->makeWhereStringForPrepare($opts['where']);
            $whereSql = " WHERE " . $whereRs['where'];
            $dataDb = array_merge($dataDb, $whereRs['data']);
        }

        $sql = "select {$opts['fields']} from {$table} {$whereSql}";

        if ($opts['group']) {
            if (FALSE === strpos(strtoupper($opts['group']), 'GROUP BY')) {
                $sql .= " GROUP BY ";
            }
            $sql .= $opts['group'];
        }

        if ($opts['order']) {
            if (FALSE === strpos(strtoupper($opts['order']), 'ORDER BY')) {
                $sql .= " ORDER BY ";
            }
            $sql .= $opts['order'];
        }

        if (0 <= $opts['page'] && 0 <= $opts['perpage']) {
            ($opts['page'] == 0) && ($opts['page'] = 1);
            ($opts['perpage'] == 0) && ($opts['perpage'] = 20);
            $offset = ($opts['page'] - 1) * $opts['perpage'];
            $sql .= " limit {$offset}, {$opts['perpage']}";
        }

        return $this->prepareSql($sql, $dataDb);
    }

    /**
     * Insert
     *
     * @param array $data
     * @param string $table
     * @return boolean
     */
    public function prepareInsert($data, $table) {
        $keys = array_keys($data);
        $valueRs = array_values($data);

        $keyStr = implode(',', $keys);
        $valueStr = str_pad('?', (count($keys) * 2 - 1), ',?');

        $sql = "INSERT INTO {$table} ({$keyStr}) VALUES ({$valueStr});";

        return $this->prepareSql($sql, $valueRs);
    }

    /**
     * Update table
     *
     * @param array $data
     * @param string $where
     * @param string $table
     * @return int
     */
    public function prepareUpdate($data, $where, $table) {
        $fieldStr = $whereSql = NULL;
        $dataDb = array();

        foreach ($data as $key => $value) {
            (NULL != $fieldStr) && $fieldStr .= ',';
            $fieldStr .= $key . "=?";
            $dataDb[] = $value;
        }

        if ($where) {
            $where = $this->makeWhereStringForPrepare($where);
            $whereSql = $where['where'];
            $dataDb = array_merge($dataDb, $where['data']);
        }

        $sql = "UPDATE {$table} SET {$fieldStr} WHERE {$whereSql}";

        return $this->prepareSql($sql, $dataDb);
    }

    /**
     * Delete from table
     *
     * @param string $where
     * @param string $table
     * @return int
     */
    public function prepareDelete($where, $table) {

        $whereSql = null;
        $dataDb = array();

        if ($where) {
            $whereRs = $this->makeWhereStringForPrepare($where);
            $whereSql = $whereRs['where'];
            $dataDb = $whereRs['data'];
        }

        $sql = "DELETE FROM $table WHERE $whereSql";

        return $this->prepareSql($sql, $dataDb);
    }

    /**
     * Count num rows
     *
     * @param string $where
     * @param string $table
     * @return int
     */
    public function prepareCount(array $where, $table, $group = null) {

        $sql = "select count(1) as cnt from {$table} ";
        $data = array();

        if ($where) {
            $where = $this->makeWhereStringForPrepare($where);
            $sql .= ' where ' . $where['where'];
            $data = $where['data'];
        }

        if ($group) {
            $sql .= ' group by ' . $group;
        }

        $result = $this->prepareSql($sql, $data);
        return empty($result[0]['cnt']) ? 0 : $result[0]['cnt'];
    }

    /**
     * 拼凑WHERE条件。其实oracle 的number 字段也可以加单引号查询。
     * 此方法需重构,如果查询的是日期格式
     * 
     * Todo 优化
     * 
     * @param array $where 条件数组
     * @example $where = array(
     *      'GRBSM' => '1111', //GRBSM字段的值为1111
     *      'FROM_TYPE' => array( //FORM_TYPE 字段的值在(5,6)中
     *           'field' => 'FROM_TYPE',
     *           'operator' => ' in ',
     *           'value' => array(5,6),
     *           'splitCharStart' => '',
     *           'splitCharEnd' => ''
     *       ),
     *      'ID' => array(
     *           'field' => 'ID',
     *           'operator' => ' > ',
     *           'value' => 1000,
     *           'splitCharStart' => '',
     *           'splitCharEnd' => ''
     *       )
     *      
     * )
     * 
     * @param string $operator 操作符， like 模糊查询
     * @return array
     * @example array(
     *      'where' => " GRBSM=? and FROM_TYPE in (?,?) and ID>?",
     *      'data'  => array('1111','5','6','1000')
     * ) 
     */
    public function makeWhereStringForPrepare($where) {

        $whereStr = null;
        $data = array();

        $splitCharStart = $splitCharEnd = "'";
        foreach ((array) $where as $k => $v) {

            ($whereStr != null) && $whereStr .= ' and ';

            if (is_array($v)) { //二级条件，自定义条件数据对象
                $matchOperator = strtoupper(E_Tools::strim($v['operator']));

                if (in_array($matchOperator, array('IN', 'NOT IN'))) { //in语句特别封装
                    $currField = $v['field'];
                    $operator = $v['operator'];
                    $currValue = '(' . str_pad('?', (count($v['value']) * 2 - 1), ',?') . ')';
                    $data = array_merge($data, $v['value']);
                    $splitCharStart = $v['splitCharStart'];
                    $splitCharEnd = $v['splitCharEnd'];
                } else {
                    $currField = $v['field'];
                    $operator = $v['operator'];
                    $currValue = ('~' == $v['value'][0]) ? substr($v['value'], 1) : '?';
                    if ('~' != $v['value'][0]) {
                        $data[] = ('LIKE' == $matchOperator) ? '%' . $v['value'] . '%' : $v['value'];
                    }
                    $splitCharStart = ('LIKE' == $matchOperator) ? '' : $v['splitCharStart'];
                    $splitCharEnd = ('LIKE' == $matchOperator) ? '' : $v['splitCharEnd'];
                }

                //or 强制加小括号
                if ('OR' == trim(strtoupper($v['operator']))) { //in语句特别封装
                    ('(' != $splitCharStart) && $splitCharStart = '(';
                    (')' != $splitCharEnd) && $splitCharEnd = ')';
                }
            } else if (in_array(strtolower($v), $this->_nullRs)) {
                //is null 和 is not null
                $currField = $k;
                $operator = ' ';
                $currValue = '?';
                $data[] = $v;
                $splitCharStart = $splitCharEnd = '';
            } else {
                $currField = $k;
                $currValue = '?';
                $data[] = $v;
                $operator = ' = ';
                $splitCharStart = $splitCharEnd = '';
            }

            $whereStr .= $currField
                    . ' '
                    . $operator
                    . ' '
                    . $splitCharStart
                    . $currValue
                    . $splitCharEnd;
        }

        return array('where' => $whereStr, 'data' => $data);
    }

    /**
     * 拼凑WHERE条件。其实oracle 的number 字段也可以加单引号查询。
     * 此方法需重构,如果查询的是日期格式
     * 
     * @param array $where 条件数组
     * @example $where = array(
     *      'GRBSM' => '1111', //GRBSM字段的值为1111
     *      'FROM_TYPE' => array( //FORM_TYPE 字段的值在(5,6)中
     *           'field' => 'FROM_TYPE',
     *           'operator' => ' in ',
     *           'value' => '(5,6)',
     *           'splitCharStart' => '',
     *           'splitCharEnd' => ''
     *       )
     * )
     * 拼装后是这样： GRBSM='1111' and FROM_TYPE in (5.6)
     * 
     * @param string $operator 操作符， like 模糊查询
     * @return string
     */
    public function makeWhereString($where, $operator = null) {
        $whereStr = null;

        $percent = ($operator == 'like') ? '%' : '';
        $operator = $oemOperator = $operator ? ' ' . $operator . ' ' : '=';
        $splitCharStart = $splitCharEnd = "'";
        foreach ((array) $where as $k => $v) {
            ($whereStr != null) && $whereStr .= ' and ';
            if (is_array($v)) {
                //二级条件
                $currField = $v['field'];
                $operator = $v['operator'];
                $currValue = $v['value'];
                $splitCharStart = $v['splitCharStart'];
                $splitCharEnd = $v['splitCharEnd'];
            } else if (in_array(strtolower($v), $this->_nullRs)) {
                //is null 和 is not null
                $currField = $k;
                $operator = ' ';
                $currValue = $v;
                $splitCharStart = $splitCharEnd = '';
            } else {
                $currField = $k;
                $currValue = $v;
                $operator = $oemOperator;
                $splitCharStart = $splitCharEnd = "'";
            }
            $whereStr .= $currField
                    . $operator
                    . $splitCharStart
                    . $percent
                    . $currValue
                    . $percent
                    . $splitCharEnd;
        }
        return $whereStr;
    }

    /**
     * Throw error exception
     *
     */
    protected function _throwException() {
        $error = $this->error();
        throw new E_Db_Exception($error['msg'], $error['code']);
    }

    abstract public function connect();

    abstract public function reConnection($msg);

    abstract public function checkConn();

    abstract public function close();

    abstract protected function _prepareQuery($sql);

    abstract public function affectedRows();

    abstract public function fetch();

    abstract public function fetchAll();

    abstract public function lastInsertId();

    abstract public function beginTransaction();

    abstract public function commit();

    abstract public function rollBack();

    abstract public function free();

    abstract public function escape($str);
}
