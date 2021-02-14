<?php

namespace beingnikhilesh\sms\Error;

/*
 * Library to hold the error details or the success status
 * 
 * Dependencies
 *  Set the DETAILED_ERROR constant
 * 
 * Version 
 * v0.0.4
 * 
 * Changes
 *  v0.0.2
 *      The self::check_error() function now return FALSE if success is set and hence if a tasked has been already finished before executing its helpfull.
 *      New function appended get_success_data() which returns the success data.
 *      The self::get_returndata() Now returns warnings(Class 2 message) appended to the message.
 * 
 *  v0.0.3
 *      Now, The library does not throw error if DETAILED_ERROR constant is not set
 * 
 *  v0.0.4
 *      Now we can even store the process log trail in the file
 * 
 */

class Error {

    //The variable to hold the error data
    private static $error = [];
    //The boolean variable to check if error exists
    private static $error_set = 0;
    //Set the status
    private static $status = 1;
    //Set the success status
    private static $success_status = '';
    //Set the success data
    private static $success_data;
    //Set the success data indicator
    private static $success_data_set = 0;

    function __construct() {
        //Load the CI Instance
        $CI = & get_instance();
        self::$error = [
            'warnings' => [],
            'error_messages' => [],
            'debug' => [],
            'other' => []
        ];

        //Check if detailed error is defined
        defined('DETAILED_ERROR') or define('DETAILED_ERROR', 0);
    }

    /*
     *  Function to call the error message placeholder
     * 
     *  @param      $error_message      String to set as error message
     *  @param      $error_type         Type of error
     *                                      0 - Other Errors
     *                                      1 - Error Messages
     *                                      2 - Warnings
     */

    public static function set_error($error_message, $error_type = 1) {
        //Validate the input
        if ($error_message == '') {
            self::set_error_message('Invalid Error Input Provided', 1);
        }

        //Set the actual Error
        self::set_error_message($error_message, $error_type);
    }

    /*
     * Public Function to clear all the error in the library
     */

    public static function clear_errors() {
        //Set the error variable
        self::$error_set = 0;
        //Change the status variable
        self::$status = 1;
        self::$error = [
            'warnings' => [],
            'error_messages' => [],
            'debug' => [],
            'other' => []
        ];
    }

    /*
     *  Function to call the debug message placeholder
     * 
     *  @param      $error_message      String to set as debug message
     */

    public static function debug($error_message) {
        //Validate the input
        if ($error_message == '') {
            self::set_error('Invalid Debug Input Provided');
        }

        //Set the actual Error
        self::set_error_message($error_message, 3);
    }

    /*
     *  The public function to be used to set the success message
     * 
     *  @param     $message     Success Message to be set
     */

    public static function set_successmessage($message) {
        //Verify the input
        if ($message == '') {
            self::set_error('The Message provided is not in the appropriate form.');
        }

        //Set the message
        self::$success_status = $message;
    }

    /*
     *  The public function to be used to set the success message
     * 
     *  @param     $message     Success Message to be set
     */

    public static function set_successdata($message) {
        //Verify the input
        if ($message == '') {
            self::set_error('The Message provided is not in the appropriate form.');
        }

        //Set the indicator
        self::$success_data_set = 1;

        //Set the message
        self::$success_data = $message;
    }

    /*
     *  Function to call the error message placeholder
     * 
     *  @param      $error_message      String to set as error message
     *  @param      $error_type         Type of error
     *                                      0 - Other Errors
     *                                      1 - Error Messages
     *                                      2 - Warnings   
     */

    private static function set_error_message($error_message, $error_type) {
        //We would not validate the inputs as it is been send by our own method
        /*
         * The actual error is set in the Form
         *      array(
         *          'message' => message
         *          'line_no' => Line no from which the error originated
         *          'method' => Method from which the error originated
         *          'class' => The current class name
         *          'function' => The function generating the message
         *          'time' => date('Y-m-d H:i:s')
         *      )
         *
         */

        //Load the CI Instance
        $CI = & get_instance();

        //Define the variables
        //The backtrace array  
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        //The count of the array
        $n = count($backtrace);
        //The error data storage
        $error_data = [];
        //error heirarchy variable
        $heirarchy_tree = '';

        //Create the error heirarchy variable
        for ($k = 2; $k < ($n - 2); $k++) {
            $heirarchy_tree = $backtrace[$k]['class'] . "[" . $backtrace[$k]['function'] . "()]" . (($k == 2) ? '' : '->') . $heirarchy_tree;
        }


        //Create the array
        $error_data = array(
            'message' => $error_message,
            'line_no' => $backtrace[$n - 4]['line'],
            'method' => $backtrace[$n - 3]['function'],
            'class' => $backtrace[$n - 3]['class'],
            'function' => $backtrace[$n - 4]['function'],
            'time' => date('Y-m-d H:i:s'),
            'error_heirarchy' => $heirarchy_tree
        );

        //Store the error data
        if ($error_type == 1) {
            //Set the error variable
            self::$error_set = 1;
            //Change the status variable
            self::$status = -2;
            self::$error['error_messages'][] = $error_data;
        } elseif ($error_type == 2) {
            self::$error['warnings'][] = $error_data;
        } elseif ($error_type == 3) {
            self::$error['debug'][] = $error_data;
        } else {
            //Set the error variable
            self::$error_set = 1;
            //Change the status variable
            self::$status = -2;
            self::$error['other'][] = $error_data;
        }
    }

    /*
     *  Function to get the error
     * 
     *  @param      $view               Need an extended view or condensed view
     *  @return     string   
     */

    public static function get_error($view = FALSE) {
        /*
         * We've to decide if the user needs an extended view or a normal view
         *  Normal View
         *      The error Message
         * Extended View
         *      The error Message
         *      Line - 32, Method - Method Name, Class - Class Name 
         *      Function - Error Function Time - 10-9-2014 01:45:00
         * 
         */

        //Load the CI Instance
        $CI = & get_instance();

        //Declare the varibles
        $return_variable = '';
        
        //Merge all the errors
        foreach (self::$error['error_messages'] as $key => $val) {
            $total_resulting_array[] = $val;
        }
        if(isset(self::$error['other']))
            foreach (self::$error['other'] as $key => $val) {
                $total_resulting_array[] = $val;
            }
        if(isset(self::$error['warnings']))
            foreach (self::$error['warnings'] as $key => $val) {
                $total_resulting_array[] = $val;
            }
        if(isset(self::$error['debug']))
            foreach (self::$error['debug'] as $key => $val) {
                $total_resulting_array[] = $val;
            }

        //If error is set
        if (self::$error_set) {
            //Return the error message
            foreach ($total_resulting_array as $key => $val) {
                $return_variable .= $val['message'] . '<br />';
                //The user needs detailed view
                if (DETAILED_ERROR) {
                    //$return_variable .= 'Line - '.$val['line_no'].', Method - '.$val['method'].', Class - '.$val['class']. '<br />';
                    //$return_variable .= 'Function - '.$val['function'].', Time - '.$val['time'].'<br />';
                    $return_variable .= 'Error Line - ' . $val['line_no'] . ', Calling Function - ' . $val['method'] . ', Calling Class - ' . $val['class'] . '<br />';
                    $return_variable .= 'Executing Function - ' . $val['function'] . ', Time - ' . $val['time'] . '<br />';
                    $return_variable .= 'Heirarchy - ' . $val['error_heirarchy'] . '<br />';
                }
            }
        } else {
            return [self::$status, 'No errors in the execution.'];
        }

        return [self::$status, $return_variable];
    }

    /*
     *  Function to get the error
     * 
     *  @param      $view               Need an extended view or condensed view
     *  @return     string   
     */

    public static function get_success_data($view = TRUE, $error_data = FALSE) {
        /*
         * We've to decide if the user needs an extended view or a normal view
         *  Normal View
         *      The Success Message and the warnings
         * Extended View
         *      The Success Message
         *      But there are still warnings
         *          The warning message
         *              Line - 32, Method - Method Name, Class - Class Name 
         *              Function - Error Function Time - 10-9-2014 01:45:00
         *          The warning message
         *              Line - 32, Method - Method Name, Class - Class Name 
         *              Function - Error Function Time - 10-9-2014 01:45:00
         * 
         */

        //Load the CI Instance
        $CI = & get_instance();

        //First of all check if success data is set
        if (self::$success_data_set) {
            //We've to return the data set
            return [1, self::$success_data];
        }

        //Declare the varibles
        $return_variable = '';
        $total_resulting_array = [];

        if (DETAILED_ERROR) {
            $error_data = TRUE;
        }

        //Merge all the Warnings
        if (isset(self::$error['warnings']) AND (count(self::$error['warnings']) > 0)) {
            foreach (self::$error['warnings'] as $key => $val) {
                $total_resulting_array[] = $val;
            }
        }


        //If error is set
        if (!self::$error_set && count($total_resulting_array) > 0) {
            //Append the message first
            $return_variable .= '<b>' . self::$success_status . '</b><br />';
            $return_variable .= '   But there are some warnings.<br />';
            //Return the error message
            foreach ($total_resulting_array as $key => $val) {
                $return_variable .= (($error_data) ? '<b>      ' . $val['message'] . '</b><br />' : '      ' . $val['message'] . '<br />');
                //The user needs detailed view
                if ($error_data) {
                    $return_variable .= '      Error Line - ' . $val['line_no'] . ', Calling Function - ' . $val['method'] . ', Calling Class - ' . $val['class'] . '<br />';
                    $return_variable .= '      Executing Function - ' . $val['function'] . ', Time - ' . $val['time'] . '<br />';
                    $return_variable .= '      Heirarchy - ' . $val['error_heirarchy'] . '<br />';
                }
            }
        } else {
            return [1, self::$success_status];
        }

        return [1, $return_variable];
    }

    /*
     *  The function to set the exclusive status of the errors
     * 
     *  @param     $status      status of the error message.
     */

    public static function set_exclusive_errorstatus($status) {
        //Verify the inputs
        if (!is_numeric($status)) {
            self::set_error('The status provided is not in the appropriate form.');
        }

        self::$status = $status;
        return;
    }

    /*
     *  The public function to be used by all the controllers while data returning
     * 
     *  @return     $status      status of the error message.
     */

    public static function get_returndata() {
        if (!self::$error_set) {
            return self::get_success_data();
        } else {
            return self::get_error();
        }
    }

    /*
     *  The public function to check if error is present
     * 
     *  @param      $check_success  Boolean
     *  @return     boolean TRUE/FALSE
     */

    public static function check_error($check_success = FALSE) {
        /*
         *  If $check_success is set means that we need to check if success message is set and return false if yes
         */

        if (self::$error_set > 0) {
            //There is error
            return FALSE;
        }

        if ($check_success) {
            $return_data = self::get_returndata();
            if ($return_data[0] == 1 && self::warnings_set()) {
                return FALSE;
            }
        }

        //Means there is no error yet.
        return TRUE;
    }

    public static function warnings_set() {
        if (count(self::$error['warnings']) > 0) {
            return TRUE;
        }

        return FALSE;
    }

}
