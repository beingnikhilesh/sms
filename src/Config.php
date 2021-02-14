<?php

namespace beingnikhilesh\sms;

class Config {

    private $config = [
        /*
          |--------------------------------------------------------------------------
          | Is SMS Enabled
          |--------------------------------------------------------------------------
          |
          | This value determines whether the SMS is Enabled
          |
         */
        'enabled' => TRUE,
        /*
          |--------------------------------------------------------------------------
          | Default Driver
          |--------------------------------------------------------------------------
          |
          | This value determines which of the following gateway to use.
          |
         */
        'default' => 'smpp',
        /*
          |--------------------------------------------------------------------------
          | Maximum Recipients
          |--------------------------------------------------------------------------
          |
          | This value determines how many recipients should be included in a single Batch
          |
         */
        'max_recipients' => 90,
        /*
          |--------------------------------------------------------------------------
          | Maximum No of SMS to send at a time
          |--------------------------------------------------------------------------
          |
          | This value determines the Maximum no of Characters to Send in a Single SMS
          |
         */
        'max_characters' => 1000,
        /*
          |--------------------------------------------------------------------------
          | List of Drivers
          |--------------------------------------------------------------------------
          |
          | These are the list of drivers to use for this package.
          | You can change the name. Then you'll have to change
          |
         */
        'drivers' => [
            'shiv' => [
                'username' => '',
                'password' => '',
                'senderid' => ''
            ],
            'textlocal' => [
                'url' => 'http://api.textlocal.in/send/', // Country Wise this may change.
                'username' => 'Your Username',
                'hash' => 'Your Hash',
                'senderid' => 'MAFCOC',
            ],
            'smpp' => [
                'username' => '',
                'password' => '',
                'senderid' => ''
            ]
        ]
    ];
    
    /*
     * @var String
     */
    protected $default = '';
    
    /*
     * Construct Function
     */
    function __construct($provider = ''){
        if($provider != '')
            $this->set_provider($provider);
        else
            $this->default = $this->config['default'];
    }
    
    /*
     * Function to set the SMS Provider
     */
    public function set_provider($provider){
        if($provider == ''){
            \sms\Error\Error::set_error('Driver not selected');
            return;
        }
        
        if(isset($this->config['drivers'][$provider]))
            $this->default = $this->config['default'] = $provider;
        else
            \sms\Error\Error::set_error('Invalid Gateway / Provider Passed');
    }
    
    /*
     * Function to get the Provider
     */
    public function get_provider_name(){
        return $this->default;
    }
    
    /*
     * Function to get the default Provider and settings
     */
    public function get_provider(){
        $return = $this->config;
        
        if(!isset($this->default) OR !isset($this->config['drivers'][$this->default])){
            \sms\Error\Error::set_error('Invalid or Default Provider Not Set. <br />
                Please Set the Default Provider in beingnikhilesh\SMS\Config Config File');
        }else{
            $return['drivers'] = $this->config['drivers'][$this->default];
            return $return;
        }
    }

}
