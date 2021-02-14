<?php

namespace beingnikhilesh\sms;

use beingnikhilesh\sms\Utils\Recipients;

################################################################################
#
# Class SMSBuilder
# v0.0.1
# Updated 21.4.2019
# 
# Class to accept the Parameters for Sending a Message
# 
# v0.0.1 21.4.2019
#
#
################################################################################

class SMSBuilder{
    /**
     * @var Object
     */
    protected $provider;
    
    /**
     * @var Object
     */
    protected $transaction_id;
    
    /**
     * @var Object
     */
    
    public $senderid;
    /**
     * @var Bool
     */
    protected $via = '';
    
    /**
     * @var Bool
     */
    public $transactional = TRUE;
    
    /**
     * @var Bool
     */
    protected $flash = FALSE;
    
    /**
     * @var Bool
     */
    protected $unicode = TRUE;
    
    /**
     * @var number
     */
    protected $schedule = '';
    
    /*
     * Construct Function
     */
    function __construct(){
        
    }
    
    /*
     * Public Function to Set the Transaction ID
     * Optional
     */
    public function transaction_id($transaction_id){
        if($transaction_id != '')
            $this->transaction_id = $transaction_id;
        return $this;
    }
    
    /*
     * Public Function to set to whom the Message is to be Sent 
     */
    public function to($recipients){
        if(!is_array($recipients))
            $recipients = [$recipients];
        
        $this->recipients = new Recipients($recipients);
        return $this;
    }
    
    /*
     * Public Function to Set the Sender ID of the SMS
     * The Sender ID set explicitly sets the Precedence over the One from the Database
     * OPTIONAL
     */
    public function senderid($senderid){
        $this->senderid = new Utils\SenderID($senderid);
        return $this;
    }
    
    /*
     * Public Function to Set the Gateway / Provider to Send the Message
     */
    public function via($gateway){
       $this->provider = new \sms\Config();
       $this->provider->set_provider($gateway);
       $this->via = $this->provider->get_provider_name();
        
       return $this;
    }
    
    /*
      |-------------------------------------------------------------------------
      | Message Text Factory
      |-------------------------------------------------------------------------
     */
    
    /*
     * Public Function to Accept the SMS and its values
     */
    public function text($message, $values = []){
       $this->message = $Object = new \sms\Message();
       $Object->set($message, $values);
       return $this;
    }
    
    /*
      |-------------------------------------------------------------------------
      | Ancillary Functions
      |-------------------------------------------------------------------------
     */
    
    /*
     * Public Function to set the message as UTF8 i.e. Devnagari
     */
    public function transactional($status){
        //Validate the Input
        if(!in_array($status, [1, 0])){
            \sms\Error\Error::set_error('Invalid Input to Set the Message as Transactional');
            return;
        }
        
        $this->transactional = $status;
        return $this;
    }
    
    /*
     * Public Function to set the message as Flash
     */
    public function flash($status){
        //Validate the Input
        if(!in_array($status, [1, 0])){
            \sms\Error\Error::set_error('Invalid Input to Set the Message as Flash');
            return;
        }
        
        //The Flash Mode is by default set to FALSE
        $this->flash = $status;
        return $this;
    }
    
    /*
     * Public Function to set the message as UTF8 i.e. Unicode
     */
    public function unicode($status){
        //Validate the Input
        if(!in_array($status, [1, 0])){
            \sms\Error\Error::set_error('Invalid Input to Set the Message as Unicode');
            return;
        }
        
        $this->unicode = $status;
        return $this;
    }
    
    /*
     * Public Function to schedule a message, pass the time in unicode format
     */
    public function schedule($time){
        if(!is_numeric($time) OR ($time < time()))
            return $this;
        
        $this->schedule = $time;
        return $this;
    }

    /*
      |-------------------------------------------------------------------------
      | Final Functions
      |-------------------------------------------------------------------------
     */
    function send(){
        //Satisfy the Compulsary Requirements
        $this->_satisfy_reqments();
        
        //Verify all the Preliminary Inputs
        $this->_validate_send();
        if (!\sms\Error\Error::check_error())
            return;
        
        //Select the Provider and send the message through it
        //Check if class exist
        $class = '\sms\Provider\\'.$this->via;
        if(!class_exists($class)){
            \sms\Error\Error::set_error('Fatal Error: Settings for the Provider / Gateway are not Available.');
        }else{
            $action = new $class();
            $return = $action->send($this);
        }
        
        //Check for Errors before sending the Details
        if (!\sms\Error\Error::check_error()){
            return $this->transaction_id;
        }else{
            //Update the Database
            $db = \sms\DB\DBUpdate::DBInsert($this->transaction_id, '', $this->via, $return['variables'], $return['error_reponse']);
            return $db;
        }
            
        
    }
    
    /*
     * Function to Satisfy the Other Requirements
     * i.e. Provider, SenderID
     */
    private function _satisfy_reqments(){
        //Check if Provider is set
        if(! is_a($this->provider, \sms\Config::class)){
            $this->provider = new \sms\Config();
            if (!\sms\Error\Error::check_error())
                return;
            
            $settings = $this->provider->get_provider();
            $this->via = $this->provider->get_provider_name();
        }else{
            $settings = $this->provider->get_provider();
        }
        
        //Check if SenderID is Set
        if(! is_a($this->senderid, \sms\Utils\SenderID::class) AND is_a($this->provider, \sms\Config::class)){
            //Set the Sender ID
            if(isset($settings['drivers']['senderid'])){
                $this->senderid($settings['drivers']['senderid']);
            }
        }
        
        //Check if SMS is Enabled
        if(!$settings['enabled'])
            \sms\Error\Error::set_error('SMS have been Disabled. Please Enable in the Config File');
    }
    
    /*
     * Private Function to validate before Send
     */
    private function _validate_send(){
        //Check for Errors even before Checking the Set Elements
        if (!\sms\Error\Error::check_error())
            return;
        
        if (! is_a($this->senderid, \sms\Utils\SenderID::class)) {
            \sms\Error\Error::set_error('Invalid or No sender ID Provided');
        }
        
        if (! is_a($this->recipients, \sms\Utils\Recipients::class)) {
            \sms\Error\Error::set_error('Invalid or No Recipient\'s Selected');
        }
        
        if (! is_a($this->message, \sms\Message::class)) {
            \sms\Error\Error::set_error('Invalid or No Message Provided');
        }
        
        if (! is_a($this->provider, \sms\Config::class)) {
            \sms\Error\Error::set_error('Invalid or No Message Provider Given');
        }
    }
    
    /*
      |-------------------------------------------------------------------------
      | Get Functions
      |-------------------------------------------------------------------------
     */
    public function get_transactional(){
        return $this->transactional;
    }
    
    public function get_unicode(){
        return $this->unicode;
    }
    
    public function get_flash(){
        return $this->flash;
    }
    
    public function get_schedule(){
        return $this->schedule;
    }
    
}