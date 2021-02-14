<?php

namespace beingnikhilesh\sms\Utils;

/*
 |------------------------------------------------------------------------------
 |  Class: Recipients
 |  v0.0.1
 |------------------------------------------------------------------------------
 |  Updated 21.4.2019
 | 
 |  Class to perform various Operations on the Recipient Numbers
 |  
 |  v0.0.1 21.4.2019
 | 
 |  
 */

class Recipients{
    /**
     * @var Array
     */
    protected $recipients = [];
    
    function __construct($recipients = []){
        if(!empty($recipients))
            $this->set_recipients($recipients);
    }
    
    function set_recipients(array $recipients){
        //Validate the Recipients
        $validate = $this->_validate_recipients($recipients);
        if($validate != FALSE)
            $this->recipients = $validate;
    }
    
    /*
     * Validate and return the Update Numbers
     */
    private function _validate_recipients($recipients){
        $numbers = [];
        
        foreach($recipients AS $key => $val){
            //Check if $val is numeric
            if(!is_numeric($val))
                continue;
            
            if(is_numeric($val) AND (strlen($val) == 10)){
                $numbers[] = $val;
            }else{
                if(substr($val, 0, 3) == '+91' AND (strlen($val) == 13)){
                    //The Numbers are preceded with +91
                    $numbers[] = substr($val, 3);
                }
                
                if(substr($val, 0, 2) == '91' AND (strlen($val) == 12)){
                    //The Numbers are preceded with 91
                    $numbers[] = substr($val, 2);
                }
            }
        }
        
        return $numbers;
    }
    
    /*
     * Function to get the Recipient Array List as a whole / Chunks
     * 
     * Some Providers send a maximum of 100 SMS at a time
     */
    function get_recipients($part = 0){
        return ($part != 0) ? array_chunk($this->recipients, $part) : $this->recipients;
    }
    
    
    
}

