<?php

/**
 * 独立应用接口基类
 * 
 * @author soft456<soft456@gmail.com>
 */
abstract class M_Interface extends M_Base {

    const API_SUCCESS_CODE = 2000; //成功
    const API_MISS_METHOD_CODE = 4001; //缺少接口名称
    const API_ERROR_VERSION_CODE = 4002; //缺少版本号
    const API_NOT_EXISTS_CODE = 4004; //接口不存在
    const API_ERROR_PARAMS_CODE = 4009; //参数错误
    const API_ERROR_RISH_CODE = 4030; //风控系统拒绝此次访问
    const API_IS_PROCESSED_CODE = 4031; //上一次请求还在处理中
    const API_ERROR_TIMEOUT = 4080; //网络超时
    const API_ERROR_SERVICE_CODE = 5001; //服务不可用
    const API_ERROR_OTHER_CODE = 5009; //其他错误    
    const API_ERROR_REDIS_CODE = 6001; //redis错误
    const API_ERROR_DB_CODE = 7001; //数据库类加载错误
    const API_ERROR_DB_QUERY = 7002; //执行SQL语句错误    
    const API_ERROR_MCRYPT = 8001; //加解密失败
    //token错误
    const API_ERROR_TOKEN = 8006;
    //数字签名不一致
    const API_ERROR_SIGN = 8007;

    /**
     *  入口参数错误并返回
     * 
     * @return array
     */
    public function retParamError() {
        return $this->retApiData(array(), M_Interface::API_ERROR_PARAMS_CODE, '参数错误！');
    }

    /**
     * 封装内部接口数据格式
     * 
     * @param mixed $data  返回的具体数据,接口执行后的返回值，可以是数组，也可以是其他类型
     * @param int $code  接口编码,编码分为系统级编码和接口级编码。系统级编码固定为4位数字，接口级编码请用英文自定义。
     * @param string $message 接口返回提示信息/错误信息
     * @return array 
     */
    public function retApiData($data, $code = M_Interface::API_SUCCESS_CODE, $message = '') {
        return array(
            'requestId' => E_Tools::guid(),
            'code' => $code,
            'message' => $message,
            'timestamp' => time(),
            'data' => $data
        );
    }

}
