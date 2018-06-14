<?php

class Ext_Base extends C_Base {
 /**
     * 成功提示信息
     * @return String
     */
    protected function success($message, array $data = [], $code = 2000)
    {
        return $this->json([
            'status' => $code,
            'msg' => $message,
            'data' => $data
        ]);
    }

    /**
     * 失败提示信息
     * @return String
     * @example 
     * {
        "code":"2005",
        "msg":"操作失败",
        "data":{}
        }
     */
    protected function error($message, $code = 0, array $data = [])
    {
        return $this->json([
            'status' => $code,
            'msg' => $message,
            'data' => $data
        ]);
    }
    protected function showMessage($message, $jumpUrl = 'javascript:history.back();', $second = 3)
    {     
        Yaf_Dispatcher::getInstance()->disableView();
        $script = "<script>setTimeout(function(){location.href = '{$jumpUrl}'},{$second}*1000);</script>";
        $html = "<b>".$message."</b></br>".
            "不想等待，<a href = '{$jumpUrl}'>点击这里</a>";

        $this->_response->setBody($html.$script);
        return ;

    }
    //
    /**
     * 返回json数据
     * @return String
     */
    protected function json(array $json)
    {
        Yaf_Dispatcher::getInstance()->disableView();
        header('Content-type: text/json');
        return $this->_response->setBody(json_encode($json, JSON_UNESCAPED_UNICODE));

    }

}
