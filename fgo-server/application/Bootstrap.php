<?php

/**
 * This file should be under the APPLICATION_PATH . "/application/"(which was defined in the config passed to Yaf_Application).
 * and named Bootstrap.php,  so the Yaf_Application can find it 
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {

    public function _initBootstrap() {
        $this->_config = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $this->_config);
    }

    public function _initLocalName() {
        Yaf_Loader::getInstance()->registerLocalNamespace(array('Ext'));
    }

    /**
     * [默认视图类(报错已用)]
     * @param  Yaf_Dispatcher $dispatcher [description]
     * @return [type]                     [description]
     */
    public function _initView(Yaf_Dispatcher $dispatcher) {
        $dispatcher->setView(new View(null));
    }

    /**
     * [错误处理]
     * @return [type] [description]
     */
    public function _initErrors() {
        //报错是否开启
        if ($this->_config->application->showErrors) {
            error_reporting(-1);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        // set_error_handler(array('E_Exception_Error', 'errorHandler'));
        //  set_exception_handler(array('E_Exception_Exception', 'handler'));
    }


    /**
     * layout页面布局
     */
    public function _initLayout(Yaf_Dispatcher $dispatcher) {
        Yaf_Registry::set('dispatcher', $dispatcher);
    }

}
