<?php

namespace beingnikhilesh\sms\Format;

use beingnikhilesh\sms\Provider\Provider;
use GuzzleHttp\Client;

################################################################################
#
# Class Smppsmshub
# v0.0.1
# Updated 02.08.2020
# 
# Class to act as a base Class for Sending SMS of the Format
#   http://182.18.143.11/api/mt/SendSMS?user=info.ourupdate@gmail.com&password=shivkuma&senderid=OURUPD
#       &channel=Trans&DCS=0&flashsms=0&number=919689916947&text=Welcome+to+OurUpdate&route=15
#   & some other Parameters as well
# 
# v0.0.1 02.08.2020
#        
#
#
################################################################################

class Smppsmshub implements Provider {
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

        //Iterate through the Stack of Numbers and send the Message in Batch
        foreach ($this->variables['number'] AS $key => $val) {
            $client = new \GuzzleHttp\Client();

            //Create the Numbers List and Reiterate
            $params = $this->variables;
            $params['number'] = implode(',', $val);
            //Remove request key as it makes repetition of data
            unset($params['request']);
            //Generate the URL
            $url = $this->push_url . http_build_query($params);
            //Save the URL
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
        //Return the Required and Adjusted Values
        return ['variables' => $this->variables, 'error_reponse' => $this->error_reponse];
    }

    /*
     * Function to Collect all the Data required to Execute
     * user=info.ourupdate@gmail.com&password=shivkuma&senderid=OURUPD
      &channel=Trans&DCS=0&flashsms=0&number=919689916947&text=Welcome+to+OurUpdate&route=15
     */

    private function _collect_data($SMSobject) {
        $config = new \sms\Config();
        //Get the data
        $config = $config->get_provider();

        $this->variables = [
            'senderid' => $SMSobject->senderid->get(),
            'number' => $SMSobject->recipients->get_recipients($this->max_send_numbers),
            'text' => $SMSobject->message->get(),
            'user' => $config['drivers']['username'],
            'password' => $config['drivers']['password'],
            'flashsms' => ($SMSobject->get_flash()) ? 1 : 0
        ];

        if ($SMSobject->get_transactional()) {
            $this->variables['channel'] = 'Trans';
            $this->variables['route'] = $this->trans_route;
        } else {
            $this->variables['channel'] = 'Promo';
            $this->variables['route'] = $this->promo_route;
        }

        //This is Compulsary
        if ($SMSobject->get_unicode())
            $this->variables['DCS'] = 1;
        else
            $this->variables['DCS'] = 0;

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
        if ($this->variables['senderid'] == '')
            \sms\Error\Error::set_error('Invalid or No sender ID Provided');
        if ($this->variables['text'] == '')
            \sms\Error\Error::set_error('Invalid or No Message Provided');
        if (empty($this->variables['number']))
            \sms\Error\Error::set_error('Invalid or No Recipient\'s Selected');
        if ($this->variables['user'] == '' OR $this->variables['password'] == '')
            \sms\Error\Error::set_error('Invalid Credentials');
        if (!in_array($this->variables['flashsms'], [0, 1]))
            \sms\Error\Error::set_error('Invalid Flash Status Provided');
        if (!in_array($this->variables['channel'], ['Trans', 'Promo']))
            \sms\Error\Error::set_error('Invalid SMS Channel Provided');
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
            //Store Raw Response for Later Use
            $this->variables['raw_response'][$key] = $val;
            //We've to unify the Response for Numbers to which SMS was sent
            //If MessageData is not present, it means Error
            if (isset($val['MessageData'])) {
                foreach ($val['MessageData'] AS $keyv => $valv) {
                    $response_stack['message_data'][$key][] = [
                        'number' => $valv['Number'],
                        'message_id' => $valv['MessageId'],
                        'text' => $this->variables['text'],
                        'jobid' => $response[$key]['JobId'],
                        'errorcode' => $response[$key]['ErrorCode'],
                        'message' => $response[$key]['ErrorMessage'],
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
        $this->variables['transactional'] = (isset($this->variables['channel']) AND ( $this->variables['channel'] == 'Trans')) ? 1 : 0;
        //is unicode
        $this->variables['unicode'] = (isset($this->variables['DCS']) AND ( $this->variables['DCS'] == 8)) ? 1 : 0;

        //Duplicate some fields Required for DB Insert
        $this->variables['dc'] = $this->variables['transactional'];
        $this->variables['fl'] = $this->variables['flashsms'];
        $this->variables['sid'] = $this->variables['senderid'];
        $this->variables['msg'] = $this->variables['text'];
    }

}
