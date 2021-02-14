<?php

namespace beingnikhilesh\sms;

use beingnikhilesh\sms\Company;


require("SMSBuilder.php");

class Sms extends SMSBuilder{
    
    function __construct(){
        parent::__construct();
    }
    
    
    
    function __destruct() {
        //print_r($this);
    }
    
}

