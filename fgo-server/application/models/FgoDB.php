<?php

class FgoDBModel extends M_Base {

    //conf中db名
    protected $_db = 'fgo';
    //表名
    protected $_table = 'question';
    protected $_pk = 'id';

    function getQuestionById($question_id = 0){
        $table = $this->_table;
        $result = $this->load($question_id,'*','id',$table);
        return $result;
    }
    function getQuestionList(){
        $whereArray = array();
        $opt = array(
            'fields' => "*",     //字段列表
            'where' => $whereArray,  //where条件
            //'concert' => 'add_time ASC',  // 字符串类修  排序字符串 ： add_time DESC,user_id ASC
            // 'page' => $inPage,       //第几页
            // 'perpage' => $inOffset        //每页多少条
        );
        $table = $this->_table;
        $result = $this->find($opt,$table);
        return $result;
    }

    function getQuestionsTitleByType($type= 1){
        $whereArray = array('type'=>$type);
        $opt = array(
            'fields' => "id,title",     //字段列表
            'where' => $whereArray,  //where条件
        );
        $table = $this->_table;
        $result = $this->find($opt,$table);
        return $result;
    }
    function getTypeById($id= 1){
        $table ='type';
        $result = $this->load($id,'*','id',$table);
        return $result;
    }
    function getTypeList(){
        $whereArray = array();
        $opt = array(
            'fields' => "*",     //字段列表
            'where' => $whereArray,  //where条件
        );
        $table = 'type';
        $result = $this->find($opt,$table);
        return $result;
    }

    function searchQuestion($key_word,$page = 1,$perpage =10){
        // var_dump($key_word);exit;
        $table = 'question';
        $whereArray = [
            'title'=>[
                'field' => 'title',
                'operator' => 'LIKE',
                'value' => $key_word,
            ]
        ];
        $opt = [
             'fields' => "id,title,type",     //字段列表
            'where' => $whereArray,
            'page' => $page,       //第几页
            'perpage' => $perpage        //每页多少条            
        ];
        return $this->find($opt,$table);
    }
}