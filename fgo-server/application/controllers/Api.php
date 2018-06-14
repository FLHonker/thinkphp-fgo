<?php

class ApiController extends Ext_Base {


    /**
     *  默认执行的方法
     */
    function init(){
        parent::init();
    }

    public function indexAction(){
         Yaf_Dispatcher::getInstance()->disableView();
    }
//搜索
    public function selectAction(){
        $key_word = $this->getQuery('keyword','');
        // var_dump($key_word);
        $list = (new FgoDBModel())->searchQuestion($key_word);
        if($list){
            $this->success("成功",$list,200);
        }else{
            $this->error("失败",400,[]);
        }
        
    }

    public function getPageAction(){
        $question_type = $this->getQuery('question_type',1);
        $list = (new FgoDBModel())->getQuestionsTitleByType($question_type);
        if($list){
            $this->success("成功",$list,200);
        }else{
            $this->error("失败",400,[]);
        }
        
        
    }
    public function getcontentAction(){
        $id =$this->getQuery('id',1);
        $list = (new FgoDBModel())->getQuestionById($id);
        if($list){
            $this->success("成功",$list,200);
        }else{
            $this->error("失败",400,[]);
        }
        
    }
}
