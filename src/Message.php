<?php

namespace beingnikhilesh\sms;

use beingnikhilesh\sms\DB\DBMessage;

class Message extends DBMessage {
    /*
     * @var String
     */

    protected $text_message = '';

    /*
     * @var Array
     */
    protected $raw_data = [];

    /*
     * Construct Function
     */

    function __construct() {
        parent::__construct();
    }

    /*
     * Override the Fetch Function
     */

    public function setDB($short, array $values = [], $namespace = '') {
        $DB = new \sms\DB\DBMessage($short, $namespace);

        $this->set($DB, $values);
    }

    /*
     * Function to Set a normal Message, with Variables
     */

    function set($message, array $array = []) {
        //Set and validate Message
        $this->_set_message($message);
        $this->_set_values($array);

        //Parse the data
        $this->_parse_message();
    }

    /*
     * Function to get the Parsed Message
     */

    function get() {
        return $this->text_message;
    }

    /*
     * Private Function to validate and Search or Fetch the Text Message
     */

    private function _set_message($message) {
        //Check if an Object is Set
        if (is_a($message, \sms\DB\DBMessage::class)) {
            $message = $message->fetch_message();
        }


        $validate = $this->_validate_message($message);
        //Check for errors
        if (!\sms\Error\Error::check_error()) {
            $this->raw_data['raw_message'] = $message;
            return;
        } else
            $this->raw_data['raw_message'] = $message;
    }

    /*
     * Private Function to validate and Search or Fetch the Text Message
     */

    private function _set_values($values) {
        if (!is_array($values)) {
            \sms\Error\Error::set_error('Invalid Values Provided');
            return;
        }

        $this->raw_data['raw_values'] = $values;
    }

    /*
     * Private Function to validate and Search or Fetch the Text Message
     */

    private function _parse_message() {
        foreach ($this->raw_data['raw_values'] AS $key => $value) {
            $this->raw_data['raw_message'] = str_replace('{' . $key . '}', $value, $this->raw_data['raw_message']);
        }

        $this->text_message = $this->raw_data['raw_message'];
    }

    /*
      |--------------------------------------------------------------------------
      | Miscellaneous Helper Functions
      |--------------------------------------------------------------------------
     */

    /*
     * Function to validate a message
     */

    private function _validate_message($text) {
        //Get the Max no of Messages
        $config = new \sms\Config();
        $max_character_limit = $config->get_provider();
        //Check for errors
        if (!\sms\Error\Error::check_error())
            return;

        //Get the Max Characters Limit
        $max_character_limit = @$max_character_limit['max_characters'];

        //Check the Strlen
        if (strlen($text) > $max_character_limit) {
            \sms\Error\Error::set_error('No of Characters Exceed the Maximum Permissible Limit');
            return;
        }
    }

}
