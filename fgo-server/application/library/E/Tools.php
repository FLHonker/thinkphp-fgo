<?php

/**
 *  yafext 工具类
 */
class E_Tools {

    /**
     * 去掉首尾空格，并去掉中间多余的空格，保留一个
     *
     * @param string $str 待转换的字符串
     * @return string
     */
    public static function strim($str) {
        return trim(preg_replace("/\s(?=\s)/", "\\1", $str));
    }

    /**
     *  计算字符显示的大致宽度，对齐用
     * 
     * @param string $str
     * @return int
     */
    public static function arialStrlen($str) {

        $char4 = array('f', 'i', 'j', 'l', 'r', 'I', 't', '1', '.', ':', ';', '(', ')', '*', '!', '\'');

        $lencounter = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $ch = $str[$i];
            if (ord($ch) > 128) {
                $i++;
                $lencounter++;
            } else if (in_array($ch, $char4)) {
                $lencounter+=0.4;
            } else if ($ch >= '0' && $ch <= '9') {
                $lencounter+=0.7;
            } else if ($ch >= 'a' && $ch <= 'z') {
                $lencounter+=0.7;
            } else if ($ch >= 'A' && $ch <= 'Z') {
                $lencounter+=0.8;
            } else {
                $lencounter++;
            }
        }
        return ceil($lencounter * 2);
    }

    /**
     * 获取应用名称
     * 
     * @return string
     */
    public static function getAppName() {
        preg_match('#\/data\/webapp\/www\/(.*?)\/.*?#i', $_SERVER['DOCUMENT_ROOT'], $appRs);
        return isset($appRs[1]) ? $appRs[1] : '';
    }

    /**
     *  输出调试信息
     * 
     * @param string|array $msg 要输出的数组或字符串
     * @param string $title 信息提示标题
     * @param string $spaceChar 换行符
     * @param boolean $isVarDump 是否var_dump方式输出
     */
    public static function debug($msg, $title = null, $spaceChar = '<br>', $isVarDump = FALSE) {

        $titleMsg = $title ? $title . ' ==> ' : '';
        echo $spaceChar . $titleMsg;

        if ($isVarDump) {
            var_dump($msg);
        } else {
            if (is_array($msg)) {
                print_r($msg);
            } else {
                echo $msg;
            }
        }

        echo $spaceChar;
    }

    /**
     * 将二维数组特定下标的值转换成一维数组
     * 
     * @param array $rs
     * @param string $key
     * @return array
     */
    public static function arrayTwoToOne($rs, $key) {
        $ret = array();
        foreach ($rs as $k => $v) {
            isset($v[$key]) && $ret[$k] = $v[$key];
        }
        //去重、重新排下标
        return array_merge(array_unique($ret));
    }

    /**
     *  对二维数组中value相同的某字段进行分组操作
     * 
     * @param array $data
     * @example array(
     *      0 => array(
     *              'id' => 2
     *              'name' => 'zhangsan'
     *          ),
     *      1 => array(
     *              'id' => 2
     *              'name' => '李四'
     *          ),
     *      2 => array(
     *              'id' => 9
     *              'name' => 'wangwu'
     *          )
     * )
     * 
     * @param string $key
     * @return array
     * @example array(
     *      '2' => array(
     *          0 => array(
     *              'id' => 2
     *              'name' => 'zhangsan'
     *          ),
     *          1 => array(
     *              'id' => 2
     *              'name' => '李四'
     *          )
     *      ),
     *      '9' => array(
     *          0 => array(
     *              'id' => 9
     *              'name' => 'wangwu'
     *          )
     *      )
     * )
     */
    public static function multiArrayGroup($data, $key) {

        $ret = array();
        foreach ($data as $value) {
            $ret[$value[$key]][] = $value;
        }

        return $ret;
    }

    /**
     *  检测是否内网IP地址
     * 
     * @param string $ip
     * @return boolean true是内网IP；false不是
     */
    public static function isInternalIp($ip) {
        $ipl = ip2long($ip);
        if (!$ipl) {
            return false;
        }

        $netLocal = ip2long('127.255.255.255') >> 24; //127.x.x.x
        $netA = ip2long('10.255.255.255') >> 24; //A类网预留ip的网络地址 
        $netB = ip2long('172.31.255.255') >> 20; //B类网预留ip的网络地址 
        $netC = ip2long('192.168.255.255') >> 16; //C类网预留ip的网络地址

        return $ipl >> 24 === $netLocal || $ipl >> 24 === $netA || $ipl >> 20 === $netB || $ipl >> 16 === $netC;
    }

    /**
     *  生成唯一数字
     * 
     * @param  $prefix 前缀
     * @return string
     */
    public static function makeSeq($prefix = '') {
        return $prefix .
                intval(intval(date('Y')) - 2015) .
                strtoupper(dechex(date('m'))) . date('d') .
                substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    }

    /**
     * 生成guid
     * 
     * @return string
     */
    public static function guid() {
        mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123)// "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . chr(125); // "}"
        return $uuid;
    }

    public static function createGuid($namespace = '') {
        static $guid = '';
        $uid = uniqid("", true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $data .= isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $data .= isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '';
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = '{' .
                substr($hash, 0, 8) .
                '-' .
                substr($hash, 8, 4) .
                '-' .
                substr($hash, 12, 4) .
                '-' .
                substr($hash, 16, 4) .
                '-' .
                substr($hash, 20, 12) .
                '}';
        return $guid;
    }

    /**
     * 对象转数组 
     * @param object $obj
     * @return array
     */
    public static function object2array($obj) {
        $arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ((array) $arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? self::object2array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    /**
     * 返回json串
     *  
     * @param array $data
     * @return string
     */
    public static function retJson($data) {
        return json_encode($data, JSON_FORCE_OBJECT);
    }

    /**
     *  替换字符串中的预替换变量为数组中的值
     * @example $str = '<a href="{URL}">{NAME}</a>';
     *           $data = array(
     *                  'URL'  => 'http://www.wujigang.cn',
     *                  'NAME' => '物集港'
     *           );
     *           返回值：<a href="http://www.wujigang.cn">物集港</a>
     * 
     * @param string $str 
     * @param array $data
     * @return string
     */
    public static function replaceVar($str, $data) {
        if (!$str || !$data) {
            return $str;
        }

        $rs = array();
        if (!preg_match_all('/{(.*?)}/', $str, $rs)) {
            return $str;
        }

        foreach ($rs[1] as $value) {
//            ECHO '<BR>{' . $value . '}'.' === '. $data[$value].'<BR>';
            $data[$value] && $str = str_replace('{' . $value . '}', $data[$value], $str);
        }

        return $str;
    }

}
