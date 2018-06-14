<?php

/**
 *  控制器框架层基类
 * 
 * @author soft456<soft456@gmail.com>
 */
class C_Base extends Yaf_Controller_Abstract {

    protected $_config;
    protected $_xhprof;
    protected $_input;

    /**
     *  暂时用YAF全局变量方式传递钩子自定义参数，
     *  TODO 改正本地缓存或其他更省资源和高效的方式
     * 
     * @param array $data
     */
    public function setHookData($func, array $data) {

        //key范例：gHookData_Hook_run 规则：关键词前缀_控制器名_方法名
        $hookDataKey = "gHookData_" . strtolower(str_replace("Controller", "", get_class($this))) . "_" . strtolower(str_replace("Action", "", $func));
        Yaf_Registry::set($hookDataKey, $data);
    }

    /**
     * Set response charset
     *
     * @param string $enc
     * @param string $type
     */
    public function charset($enc = 'UTF-8', $type = 'text/html') {
        header("Content-Type:$type;charset=$enc");
    }

    /**
     *  开启xhprof调试
     */
    public function xhprofStart() {
        if (!E_Request::isProgrammer()) {
            return;
        }
        if (!$this->_xhprof) {
            $this->_xhprof = new E_Xhporf();
        }
        $this->_xhprof->enable();
        return;
    }

    /**
     *  xhprof结束
     * 
     * @return 
     */
    public function xhprofStop() {
        if (!E_Request::isProgrammer()) {
            return;
        }

        if ($this->_xhprof) {
            echo $this->_xhprof->disable();
        }

        return;
    }

    /**
     * to 404
     */
    public function to404() {
        header("Location: /404.html", TRUE, 302);
        exit;
    }

    /**
     *  地址跳转
     * 
     * @param string $goUrl
     */
    public function redirect($goUrl) {
        parent::redirect($goUrl);
        exit;
    }

    /**
     * init
     */
    public function init() {

        //登陆判断
        $session = trim($this->getRequest()->get('session'));
        if ($session) {
            session_id($session);
        }

        $this->_config = Yaf_Application::app()->getConfig();
    }



    /**
     *  获取某个路由参数
     * 
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getParam($name = NULL, $default = NULL) {
        $ret = $name ? $this->getRequest()->getParam($name) : $this->getRequest()->getParam();


        return $ret ;
    }

    /**
     *  获取所有路由参数
     * @return array
     */
    public function getParams() {
        $ret = $this->getRequest()->getParams();
        return $ret;
    }

   
    /**
     *  获取get参数
     * 
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getQuery($name = NULL, $default = NULL) {
        $ret = $name ? $this->getRequest()->getQuery($name) : $this->getRequest()->getQuery();
        $xssRet = $this->_getEinputInc()->xss_clean($ret);
        return ((NULL !== $default) && !$xssRet) ? $default : $xssRet;
    }

    /**
     *  获取post参数
     * 
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getPost($name = NULL, $default = NULL) {
        $ret = $name ? $this->getRequest()->getPost($name) : $this->getRequest()->getPost();

/*        //formToken 检测
        if (!App_Formtoken::init()->isFormTokenCorrect($ret)) {
            die('Request not allowed');
        }*/

        $xssRet = $this->_getEinputInc()->xss_clean($ret);
        return ((NULL !== $default) && !$xssRet) ? $default : $xssRet;
    }


    /**
     *  获取json格式的表单数据
     * 
     * @return array
     */
    public function getPostJson() {
        $ret = json_decode(file_get_contents("php://input"), TRUE);

        return $this->_getEinputInc()->xss_clean($ret);
    }

    public function getEnv($name = NULL) {
        return $name ? $this->getRequest()->getEnv($name) : $this->getRequest()->getEnv();
    }

    public function getServer($name = NULL) {
        return $name ? $this->getRequest()->getServer($name) : $this->getRequest()->getServer();
    }

    public function getCookie($name = NULL, $default = NULL) {
        $ret = $name ? $this->getRequest()->getCookie($name) : $this->getRequest()->getCookie();
        return ((NULL !== $default) && !$ret) ? $default : $ret;
    }

    public function getFiles($name = NULL) {
        return $name ? $this->getRequest()->getFiles($name) : $this->getRequest()->getFiles();
    }

    public function isGet() {
        return $this->getRequest()->isGet();
    }

    public function isHead() {
        return $this->getRequest()->isHead();
    }

    public function isXmlHttpRequest() {
        return $this->getRequest()->isXmlHttpRequest();
    }

    public function isPut() {
        return $this->getRequest()->isPut();
    }

    public function isOption() {
        return $this->getRequest()->isOptions();
    }

    public function isCli() {
        return $this->getRequest()->isCli();
    }

    public function isDispatched() {
        return $this->getRequest()->isDispatched();
    }

    public function setDispatched() {
        return $this->getRequest()->setDispatched();
    }

    public function isRouted() {
        return $this->getRequest()->isRouted();
    }

    public function setRouted() {
        return $this->getRequest()->setRouted();
    }

    public function getBaseUri() {
        return $this->getRequest()->getBaseUri();
    }

    public function setBaseUri(string $base_uri = NULL) {
        return $this->getRequest()->setBaseUri($base_uri);
    }

    public function getRequestUri() {
        return $this->getRequest()->getRequestUri();
    }

    public function isPost() {
        return $this->getRequest()->isPost();
    }

    public function isAjax() {
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     *  获取E_Input实例
     * 
     * @param string $regName
     * @return \class
     */
    private function _getEinputInc() {
        $regName = "yafext_e_input";

        if (Yaf_Registry::has($regName)) {
            return Yaf_Registry::get($regName);
        }

        //没有则实例化        
        $eInputInc = new E_Input();
        Yaf_Registry::set($regName, $eInputInc);
        return $eInputInc;
    }

}
