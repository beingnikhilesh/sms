<?php

namespace beingnikhilesh\sms\Format;

use beingnikhilesh\sms\Provider\Provider;
use GuzzleHttp\Client;

################################################################################
#
# Class Searlised
# v0.0.1
# Updated 21.4.2019
# 
# Class to act as a base Class for Sending SMS of the Format
#   http://5.189.153.48:8080/vendorsms/pushsms.aspx?user=abc&password=xyz&
#       msisdn=919898xxxxxx,919898xxxxxx&sid=SenderId&msg=test%20message&fl=0&gwid=2
#   & some other Parameters as well
# 
# v0.0.1 21.4.2019
#        
#
#
################################################################################

class Serialised implements Provider {
    /*
     * @var Array
     */

    protected $variables = [];

    /*
     * @var String
     */
    protected $transaction_id = [];

    /*
     * Construct Function
     */

    function __construct() {
        
    }

    /*
      |------------------------------------------------------------------------------
      |  SMS Sending Functions
      |------------------------------------------------------------------------------
     */

    function send(\sms\SMSBuilder $object) {
        //Get the Details
        if (empty($object) OR ! is_a($object, \sms\SMSBuilder::class)) {
            return;
        }

        //Extract the Details
        $this->_collect_data($object);

        //Validate the Data
        $this->_validate_send();
        //Check for Errors before sending the Details
        if (!\sms\Error\Error::check_error())
            return;

        //Send the Message and Update the Database
        return $this->_send_message();
    }

    /*
      |------------------------------------------------------------------------------
      |  SMS Status Check Functions
      |------------------------------------------------------------------------------
     */

    public function status() {
        
    }

    /*
      |------------------------------------------------------------------------------
      |  Provider Balance Check Function
      |------------------------------------------------------------------------------
     */

    public function balance() {
        
    }

    /*
      |------------------------------------------------------------------------------
      |  Miscellaneous Functions
      |------------------------------------------------------------------------------
     */

    /*
     * Function to Actually send the Message and Update the Database
     */

    private function _send_message() {
        $response_stack = [];

        foreach ($this->variables['msisdn'] AS $key => $val) {
            $client = new \GuzzleHttp\Client();

            //Create the Numbers List and Reiterate
            $params = $this->variables;
            $params['msisdn'] = implode(',', $val);
            $url = $this->push_url . http_build_query($params);
            $this->variables['request'][] = $url;
            //Make a call
            $response = $client->request('GET', $url);
            //Get the Response Body
            $body = $response->getBody();
            //Stringify
            $body = (string) $body;
            //Arrange the Results
            if (json_decode($body) == null) {
                \sms\Error\Error::set_error('Invalid Response from SMS Server');
                return;
            }

            $response_stack[] = json_decode($body, TRUE);
        }

        //Adjust the values as per the Standard Input
        $this->_format_db_insert($response_stack);
        return ['variables' => $this->variables, 'error_reponse' => $this->error_reponse];
    }

    /*
     * Function to Collect all the Data required to Execute
     */

    private function _collect_data($SMSobject) {
        $config = new \sms\Config();
        //Get the data
        $config = $config->get_provider();

        $this->variables = [
            'sid' => $SMSobject->senderid->get(),
            'msisdn' => $SMSobject->recipients->get_recipients(1),
            'msg' => $SMSobject->message->get(),
            'user' => $config['drivers']['username'],
            'password' => $config['drivers']['password'],
            'fl' => ($SMSobject->get_flash()) ? 1 : 0
        ];

        if ($SMSobject->get_transactional())
            $this->variables['gwid'] = 2;
        if ($SMSobject->get_unicode())
            $this->variables['dc'] = 8;

        if (isset($SMSobject->transaction_id))
            $this->transaction_id = $SMSobject->transaction_id;
    }

    /*
     * Private function to validate the Send Data before sending the Actual Messages
     */

    private function _validate_send() {
        //Mandatory
        if (!isset($this->push_url) || $this->push_url == '')
            \sms\Error\Error::set_error('Invalid URL Passed');
        if ($this->variables['sid'] == '')
            \sms\Error\Error::set_error('Invalid or No sender ID Provided');
        if ($this->variables['msg'] == '')
            \sms\Error\Error::set_error('Invalid or No Message Provided');
        if (empty($this->variables['msisdn']))
            \sms\Error\Error::set_error('Invalid or No Recipient\'s Selected');
        if ($this->variables['user'] == '' OR $this->variables['password'] == '')
            \sms\Error\Error::set_error('Invalid Credentials');
        if (!in_array($this->variables['fl'], [0, 1]))
            \sms\Error\Error::set_error('Invalid Flash Status Provided');
    }

    /*
     * Private Function to Format the Response and insert into the DB
     */

    private function _format_db_insert($response) {
        /*
         * The Format for Input to the Database insert is as follows
         * 
         * $SMSData = [
         *  code => 000
          message => Success
          message_data => [
         *      [
         *          number => 96899189890
         *          message_id => '123456'
         *          text => 'Congratulations'
         *      ],[
         *      ...
         *      ]
         *  ]
         * ]
         */

        //Declare the Variables
        $response_stack = [];

        //Validate Input
        if (empty($response))
            return;

        //Iterate through the Stack and create a unified Stack

        foreach ($response AS $key => $val) {
            //We've to unify the Response for Numbers to which SMS was sent
            //If MessageData is not present, it means Error
            if (isset($val['MessageData'])) {
                foreach ($val['MessageData'] AS $keyv => $valv) {
                    $response_stack['message_data'][] = [
                        'number' => $valv['Number'],
                        'message_id' => $valv['MessageParts'][0]['MsgId'],
                        'text' => $valv['MessageParts'][0]['Text'],
                        'jobid' => $response[$key]['JobId']
                    ];
                }
            }
        }


        $response_stack['errorcode'] = $response[0]['ErrorCode'];
        $response_stack['message'] = $response[0]['ErrorMessage'];
        //Assign to Response
        $this->variables['response'] = $response_stack;
        //Modify some Variables before send
        
        //is Transactional
        $this->variables['transactional'] = (isset($this->variables['gwid']) AND ($this->variables['gwid'] == 2)) ? 1 : 0;
        //is unicode
        $this->variables['unicode'] = (isset($this->variables['dc']) AND ($this->variables['dc'] == 8)) ? 1 : 0;
    }

}
