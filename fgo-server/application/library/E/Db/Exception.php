<?php

class E_Db_Exception extends Exception {

    public function __construct($message) {
        echo __class__;
        var_dump($message);
    }

}
