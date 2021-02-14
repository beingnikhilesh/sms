<?php

namespace beingnikhilesh\sms\Utils;

/*
  |------------------------------------------------------------------------------
  |  Class: SenderID
  |  v0.0.1
  |------------------------------------------------------------------------------
  |  Updated 21.4.2019
  |
  |  Class to perform Operations related to SenderID
  |
  |  v0.0.1 21.4.2019
  |
  |
 */

class SenderID {

    /**
     * @var String
     */
    protected $senderid = '';
    
    /**
     * @var Array
     */
    protected $errors = [
        'INV_SENDER_ID' => 'Invalid SENDER ID'
    ];

    /*
     * Construct Functions
     */
    function __construct($senderid) {
        if (!is_null($senderid))
            $this->set_senderid($senderid);
    }

    /*
     * Set the SenderID
     */
    function set_senderid($senderid) {
        //Validate the Recipients
        $validate = $this->_validate_senderid($senderid);
        if ($validate != FALSE)
            $this->senderid = $senderid;
        else
            \sms\Error\Error::set_error('Invalid Sender ID Provided');
    }

    /*
     * Validate and return the SenderID
     */

    private function _validate_senderid($senderid) {
        return (strlen($senderid) != 6) ? FALSE : TRUE;
    }

    /*
     * Function to get the SenderID
     */

    function get() {
        return $this->senderid;
    }

}
