<?php

class IndexController extends Ext_Base {

/**
 *  默认执行的方法
 */
    public function indexAction() {
        $this->showMessage("请使用api");
    }   
/**
 * 登陆action
 */
    public function loginAction(){


        $md = new LoginModel();
        $user = $this->getPost('user_realname');
        $pass = $this->getPost("user_password");
        if($user ==="admin" &&$pass ==="123456"){
            $_SESSION['user_info'] = ["user_realname"=>$user,"user_password"=>$pass];
            $this->redirect(STATIC_PATH . '/Weeksubmit');
        }



    }
/**
 * 注销action
 */
    public function loginoutAction(){
        $md = new LoginModel();
        $md->doLoginOut();
        $this->redirect(STATIC_PATH);

    }

}
