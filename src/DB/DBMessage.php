<?php

namespace beingnikhilesh\sms\DB;

class DBMessage {
    /*
     * @var String
     */

    protected $message = '';

    /*
     * @constant String
     */

    /*
     * Construct Message
     */

    function __construct($short = '', $namespace = '') {
        //Set the Data
        if($short != '')
            $this->fetch($short, $namespace);
    }

    /*
      |--------------------------------------------------------------------------
      | Database Functions
      |--------------------------------------------------------------------------
     */

    /*
     * Function to get the Text SMS from Database
     */

    function fetch($short = '', $namespace = '') {
        if($short == '' AND $this->message != '')
            return $this->message;

        //Fetch the Message
        $message = $this->_fetch_message_db($short, $namespace);
        //Check for errors
        if (!\sms\Error\Error::check_error())
            return;
        
        $this->message = $message;
        return $message;
    }
    
    /*
     * Function to Fetch the Set Message
     */
    function fetch_message(){
        return $this->message;
    }

    /*
      |--------------------------------------------------------------------------
      | Miscellaneous Helper Functions
      |--------------------------------------------------------------------------
     */

    /*
     * Private Function to get the SMS From Database
     */

    private function _fetch_message_db($short, $namespace) {
        //Get the CI Instance
        $CI = & get_instance();

        if ($short == '') {
            \sms\Error\Error::set_error('Invalid Short Identifier for SMS Provided');
            return;
        }

        $query = $CI->db->select('message')
                ->where('status', 1)
                ->where('short_text', $short);
        if ($namespace != '')
            $query->where('namespace', $namespace);
        //Query the Database
        $query = $query->limit(1)
                ->get('mod_sms_template');

        if ($query->num_rows() <= 0) {
            \sms\Error\Error::set_error('No Message Exists for the Provided Criteria');
            return;
        }

        $message = $query->row();
        return $message->message;
    }

}
