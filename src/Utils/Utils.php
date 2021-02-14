<?php

namespace beingnikhilesh\sms\Utils;

/*
 |------------------------------------------------------------------------------
 |  Class: Utils
 |  v0.0.1
 |------------------------------------------------------------------------------
 |  Updated 21.4.2019
 | 
 |  Utility Storage Class
 |  
 |  v0.0.1 21.4.2019
 | 
 |  
 */

class Utils{
    
    /*
     * Construct Function
     */
    function __construct(){
        
    }
    
    /*
     * For Multiple Transaction Numbers at a time
     * Get Truly Unique Random Numbers without a database
     */
    function get_uuwd($increment = 1, $length = '', $prefix = ''){
        //Initialise the Varibales
        $ret_uid = [];
        $i = 0;
        //Loop increment no of times
        for($i; $i < $increment; $i++){
            $ret_uid[] = $this->get_uwd($length, $prefix);
            
            //Count Unique Values
            $ret_uid = array_unique($ret_uid);
            if(count($ret_uid) != ($i - 1)){
                $i = count($ret_uid) - 1;
            }
        }
        
        return $ret_uid;
    }

    /*
     * Get Truly Unique Random Numbers without a database 
     */

    function get_uwd($length = '', $prefix = '') {
        $key = '';
        //Set default Length to 7 Characters
        $length = ($length == '') ? 7 : $length;
        $pool = array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'));

        for ($i = 0; $i < $length; $i++) {
            $key .= $pool[mt_rand(0, count($pool) - 1)];
        }
        
        //Return the result
        return $prefix. time() . $key;
    }
}