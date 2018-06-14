<?php

/**
 * Base Model
 * 
 * @author soft456<soft456@gmail.com>
 */
abstract class M_Base {

    const ERROR_VALIDATE_CODE = -400;

    /**
     * Db name
     *
     * @var string
     */
    protected $_db = 'db';

    /**
     * Table name, with prefix and main name
     *
     * @var string
     */
    protected $_table;

    /**
     * Primary key
     *
     * @var string
     */
    protected $_pk = 'id';

    /**
     * Cache config
     *
     * @var mixed, string for config key and array for config
     */
    protected $_cache = 'memcached';

    /**
     * Cache expire time
     *
     * @var int
     */
    protected $_ttl = 60;

    /**
     * Validate rules
     *
     * @var array
     */
    protected $_validate = array();

    /**
     * Model Instance 
     * 
     * @var array 
     */
    protected static $_instance = array();

    /**
     * Error infomation
     *
     * @var array
     */
    public $errors = array();

    /**
     * find 执行后，分页数组
     * 
     * @var array
     * @example array (
     *              'page' => 1,  //当前第几页
     *              'perpage' => 2, //每页多少条
     *              'total_count' => '4', //总条数
     *              'total_page' => 2, //总页数
     *              'first_page' => 1, //第一页
     *              'end_page' => 2, //最后一页
     *              'previous_page' => 1, //上一页
     *              'next_page' => 2 //下一页
     *         )
     */
    public $pageRs = array();

    /**
     * @return self
     */
    public static function init() {
        $cls = get_called_class();

        if (isset(self::$_instance[$cls]) && is_object(self::$_instance[$cls])) {
            return self::$_instance[$cls];
        }
        return self::$_instance[$cls] = new static();
    }

    /**
     * Load data —— 获取一行数据，按参数拼装SQL语句
     * 
     * @param string $id 主键值
     * @param string $fieldList 字段列表
     * @param string $col 主键字段名
     * @param string $table 表名称
     * @return array 
     */
    public function load($id, $fieldList = '*', $col = null, $table = null) {
        is_null($col) && ($col = $this->_pk);
        is_null($table) && ($table = $this->_table);

        $sql = "SELECT {$fieldList} FROM {$table} WHERE {$col} = ?";

        $result = $this->db->prepareRow($sql, $id);
        return $result ? $result : FALSE;
    }

    /**
     * Find result——支持分页
     *
     * @param array $opts
     * @example  array(
     *       'fileds' => 'user_id,user_email,user_mobile,user_pwd',     //字段列表
     *       'where' => array(
     *           'user_status' => 1, //第一个条件：user_status =1
     *           'id' => array(//第二个条件：id > 21
     *               'field' => 'id',
     *               'operator' => ' > ',
     *               'value' => 21,
     *               'splitCharStart' => '',
     *               'splitCharEnd' => ''
     *           )
     *       ),  //where条件
     *       'order' => null,  // 字符串类修  排序字符串 ： add_time DESC,user_id ASC
     *       'group' => null,
     *       'page' => 1,       //第几页
     *       'perpage' => 20        //每页多少条
     *   );
     * @param string $table
     * @return array
     */
    public function find($opts = array(), $table = null) {
        isset($opts['fileds']) && $opts['fields'] = $opts['fileds'];
        is_string($opts) && $opts = array('where' => $opts);
        is_null($table) && $table = $this->_table;

        if (isset($opts['page']) && (0 < $opts['page'])) {
            //总条数
            isset($opts['group']) || ($opts['group'] = null);
            $allCount = $this->db->prepareCount($opts['where'], $table, $opts['group']);
            $this->pageRs = $this->makeCutPageData($opts['page'], $opts['perpage'], $allCount);
        }
        $result = $this->db->prepareFind($opts, $table);
        return $result ? $result : FALSE;
    }

    /**
     *  封装分页数据数组
     * 
     * @param int $page
     * @param int $perpage
     * @param int $allCount
     * @return array
     */
    public function makeCutPageData($page, $perpage, $allCount) {

        ($page <= 0) && ($page = 1);
        ($perpage <= 0) && ($perpage = 20);
        $allPage = ceil($allCount / $perpage);
        $nextPage = (($page + 1) > $allPage) ? $allPage : $page + 1;
        $previousPage = (($page - 1) < 1) ? 1 : $page - 1;

        return array(
            'page' => $page,
            'perpage' => $perpage,
            'total_count' => $allCount,
            'total_page' => $allPage,
            'first_page' => 1,
            'end_page' => $allPage,
            'previous_page' => $previousPage,
            'next_page' => $nextPage
        );
    }

    /**
     *  Get One result —— 获取某个字段的值
     * 
     * @param string $colName  字段名
     * @param string $id 主键值
     * @param string $col 主键字段名
     * @param string $table 表名
     * @return string|boolean
     */
    public function getOne($colName, $id, $col = null, $table = null) {
        is_null($col) && $col = $this->_pk;
        is_null($table) && $table = $this->_table;

        $sql = "select {$colName} from {$table} where {$col} = ? ";

        $result = $this->db->prepareCol($sql, $id);
        return $result ? $result : FALSE;
    }

    /**
     * Get Row result  —— 自定义SQL获取一行数据
     *
     * @param string $sql
     * @return array
     */
    public function getRow($sql, $data) {
        $result = $this->db->prepareRow($sql, $data);
        return $result ? $result : FALSE;
    }

    /**
     * 通过sql 获取数据与分页信息 —— 分页方法二 ，封装的较深(一般采用方法一)
     *
     * @param string $sql
     * @param int $page 1
     * @param int $perpage 20 每页多少条
     * @param string $url "/Admin/index/run/page/%page%";
     * @return boolean multitype
     */
    public function sqlPager($sql, $sqlData, $page, $perpage, $url, $ajax = 0) {
        intval($page) or $page = 1;
        intval($perpage) or $perpage = 20;

        $whereCountStr = preg_replace('/select(.*?)from/i', 'SELECT COUNT(1) AS CNT FROM ', $sql, 1);
        $whereCountStr = preg_replace('/order by (.*)/i', '', $whereCountStr, 1);

        $countRs = $this->db->prepareSql($whereCountStr, $sqlData);
        $count = isset($countRs[0]['CNT']) ? $countRs[0]['CNT'] : 0;

        //$sql = "select count(*) from (" . $sql . ") as sy";
        //$count = $this->db()->col($sql);

        if ($page > 0) {
            $start = ($page - 1) * $perpage;
            $limit = " limit {$start},{$perpage} ";
        }

        $data = $this->db->prepareSql($sql . $limit, $sqlData);

        $pager = new E_Pager($page, $perpage, $count, $url, $ajax);
        $html = $pager->html();
        if (!$data) {
            return false;
        }
        return array(
            'list' => $data,
            'count' => $count,
            'page' => $html
        );
    }

    /**
     * Count result
     *
     * @param array $where
     * @param string $table
     * @return int
     */
    public function count(array $where = array(), $table = null) {
        is_null($where) && $where = array(
            $this->_pk => array(//where id > 0
                'field' => $this->_pk,
                'operator' => ' > ',
                'value' => 0,
                'splitCharStart' => '',
                'splitCharEnd' => ''
            )
        );

        if (is_null($table)) {
            $table = $this->_table;
        }

        return $this->db->prepareCount($where, $table);
    }

    /**
     * Get SQL result —— 执行自定义SQL语句
     *
     * @param string $sql
     * @param array $data 
     * @return array
     */
    public function sql($sql, $data = array()) {
        $result = $this->db->prepareSql($sql, $data);
        return $result ? $result : FALSE;
    }

    /**
     * Insert
     *
     * @param array $data
     * @param string $table
     * @return boolean
     */
    public function insert($data, $table = null) {
        if (is_null($table)) {
            $table = $this->_table;
        }

        $result = $this->db->prepareInsert($data, $table);
        return $result ? $result : FALSE;
    }

    /**
     * Update
     *
     * @param int $id
     * @param array $data
     * @return boolean
     */
    public function update($id, $data, $col = null, $table = null) {
        is_null($col) && $col = $this->_pk;
        is_null($table) && $table = $this->_table;

        //$where = $col . '=' . (is_int($id) ? $id : "'$id'");
        $where = array(
            $col => $id
        );

        $result = $this->db->prepareUpdate($data, $where, $table);
        return (FALSE != $result) ? TRUE : FALSE;
    }

    /**
     * UpdateIn 批量更新
     *
     * @param array $idRs 
     * @param array $data
     * @return boolean
     */
    public function updateIn(array $idRs, $data, $col = null, $table = null) {
        is_null($col) && $col = $this->_pk;
        is_null($table) && $table = $this->_table;

        $where = array(
            $col => array(
                'field' => $col,
                'operator' => ' IN ',
                'value' => $idRs,
                'splitCharStart' => '',
                'splitCharEnd' => ''
            )
        );

        $result = $this->db->prepareUpdate($data, $where, $table);
        return (FALSE != $result) ? TRUE : FALSE;
    }

    /**
     *  Delete 
     * 
     * @param string $id 主键值
     * @param string $col 主键字段名
     * @return boolean
     */
    public function delete($id, $col = null, $table = null) {
        is_null($col) && $col = $this->_pk;
        is_null($table) && $table = $this->_table;

        $where = array(
            $col => $id
        );

        $result = $this->db->prepareDelete($where, $table);
        return (FALSE != $result) ? TRUE : FALSE;
    }

    /**
     * DeleteIn
     * 
     * @param array $idRs  主键值一维数组
     * @param string $col 主键名
     * @return boolean
     */
    public function deleteIn($idRs, $col = null, $table = null) {
        if (empty($idRs)) {
            return FALSE;
        }

        is_null($col) && $col = $this->_pk;
        is_null($table) && $table = $this->_table;

        $where = array(
            $col => array(
                'field' => $col,
                'operator' => ' IN ',
                'value' => $idRs,
                'splitCharStart' => '',
                'splitCharEnd' => ''
            )
        );

        $result = $this->db->prepareDelete($where, $table);
        return (FALSE != $result) ? TRUE : FALSE;
    }

    /**
     * Initiate a transaction
     *
     * @return boolean
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return boolean
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * Roll back a transaction
     *
     * @return boolean
     */
    public function rollBack() {
        return $this->db->rollBack();
    }

    /**
     * Get the last inserted ID.
     *
     * @param string $tableName
     * @param string $primaryKey
     * @return integer
     */
    public function lastInsertId($tableName = null, $primaryKey = null) {
        return $this->db->lastInsertId();
    }

    /**
     *  Get the last sql 
     * 
     * @return string
     */
    public function lastSql() {
        return $this->db->lastSql;
    }

    /**
     *  Get the last sqldata 
     * 
     * @return array
     */
    public function lastSqlData() {
        return $this->db->lastData;
    }

    /**
     *  Get the last sqldata 
     * 
     * @return array
     */
    public function getErrors() {
        return $this->db->errors;
    }

    /**
     * Connect db from config
     *
     * @param array $config
     * @param string
     * @return E_Db
     */
    public function db($name = null) {
        is_null($name) && $name = $this->_db;

        if (is_array($name)) {
            $config = Yaf_Application::app()->getConfig()->database->{$name}->toArray();

            $adapter = $config['adapter'];
            $class = 'E_Db_' . ucfirst($adapter);
            return new $class($config);
        }

        $regName = "_e_db_{$name}";
        if (!Yaf_Registry::has($regName)) {

            $config = Yaf_Application::app()->getConfig()->database->{$name}->toArray();
            $adapter = $config['adapter'];
            $class = 'E_Db_' . ucfirst($adapter);
            $db = new $class($config);
            Yaf_Registry::set($regName, $db);
        } else {
            $db = Yaf_Registry::get($regName);
        }
        return $db;
    }

    private function __clone() {
        
    }

    /**
     * Init E_Cache
     *
     * @param mixed $name
     * @return E_Cache
     */
    public function cache($name = null) {
        is_null($name) && ($name = $this->_cache);

        if (is_array($name)) {
            $config = Yaf_Application::app()->getConfig()->cache->{$name}->toArray();
            $adapter = $config['adapter'];
            $class = 'E_Cache_' . ucfirst($adapter);
            return new $class($config);
        }

        $regName = "_e_cache_{$name}";
        if (!Yaf_Registry::has($regName)) {
            $config = Yaf_Application::app()->getConfig()->cache->{$name}->toArray();
            $adapter = $config['adapter'];
            $class = 'E_Cache_' . ucfirst($adapter);
            $cache = new $class($config);
            Yaf_Registry::set($regName, $cache);
        } else {
            $cache = Yaf_Registry::get($regName);
        }
        return $cache;
    }

    /**
     * Get function cache
     *
     * @param string $func
     * @param mixed $args
     * @param int $ttl
     * @param string $key
     * @return mixed
     */
    public function cached($func, $args = array(), $ttl = null, $key = null) {
        is_null($ttl) && ($ttl = $this->_ttl);

        if (!is_array($args)) {
            $args = array($args);
        }

        if (is_null($key)) {
            $key = get_class($this) . '-' . $func . '-' . sha1(serialize($args));
        }

        //apcu 本地缓存
        if (function_exists('apcu_exists')) {

            if (apcu_exists($key)) {
                return apcu_fetch($key, $success);
            }

            $data = call_user_func_array(array($this, $func), $args);
            if (FALSE !== $data) {
                apcu_add($key, $data, $ttl);
            }
            return $data;
        }

        if (!$data = $this->cache->get($key)) {
            $data = call_user_func_array(array($this, $func), $args);
            $this->cache->set($key, $data, $ttl);
        }
        return $data;
    }

    /**
     * Validate
     *
     * @param array $data     
     * @param array $rules
     * @param boolean $ignoreNotExists
     * @return boolean
     */
    public function validate($data, $rules = null, $ignoreNotExists = false) {
        is_null($rules) && $rules = $this->_validate;
        if (empty($rules)) {
            return true;
        }

        $validate = new E_Validate();

        $result = $validate->check($data, $rules, $ignoreNotExists);

        if (!$result) {
            $this->error = array('code' => self::ERROR_VALIDATE_CODE, 'msg' => $validate->
                errors);
            return false;
        }

        return true;
    }

    /**
     * Dynamic set vars
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value = null) {
        $this->$key = $value;
    }

    /**
     * Dynamic get vars
     *
     * @param string $key
     */
    public function __get($key) {
        switch ($key) {
            case 'db':
                $this->db = $this->db();
                return $this->db;

            case 'cache':
                $this->cache = $this->cache();
                return $this->cache;

            case 'config':
                $this->config = Yaf_Registry::get('config');
                return $this->config;

            default:
                throw new Exception('Undefined property: ' . get_class($this) . '::' . $key);
        }
    }

}
