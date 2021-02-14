<?php

namespace beingnikhilesh\sms\DB;

/*
  |------------------------------------------------------------------------------
  |  Class: DBUpdate
  |  v0.0.1
  |------------------------------------------------------------------------------
  |  Updated 01.05.2019
  |
  |  Class to perform various Operations related to Database Update and Retrieval
  |
  |  v0.0.1 21.4.2019
  |
  |
 */

class DBUpdate {

    /**
     * @var Array
     */
    private static $transaction_id = [];

    /**
     * @var Array
     */
    private static $data = [];

    /*
     * Construct Message
     */

    function __construct() {
        
    }

    /*
      |------------------------------------------------------------------------------
      |  Insert Functions
      |------------------------------------------------------------------------------
      |
     */

    /*
     * Function to Insert the SMS Sent Data in Database
     */

    static function DBInsert($transaction_id = '', $cust_id = '', $gateway, $SMSData, $error_codes) {
        //Validate and set the Data
        self::_validate_set_data($transaction_id, $cust_id, $gateway, $SMSData, $error_codes);
        //Check for Errors before Inserting the Data
        if (!\sms\Error\Error::check_error())
            return;

        //Insert the Data
        return self::_insert_SMSData();
    }

    /*
     * Function to actually Insert the Data
     */

    private static function _insert_SMSData() {
        //Get the CI Instance
        $CI = & get_instance();
//        echo '<pre>';
//        print_r(self::$data);
//        exit;
        //Insert the Data
        if (is_array(self::$data['mod_sms_db'])) {
            $CI->db->insert_batch('mod_sms_db', self::$data['mod_sms_db']);         //Main Data
            $CI->db->insert_batch('mod_sms_batch', self::$data['mod_sms_batch']);   //Batch Data
            $CI->db->insert_batch('mod_sms_status', self::$data['mod_sms_status']); //SMS Status Data
        }

        return self::$transaction_id;
    }

    /*
     * Private Function to Verify and set the Data in place
     */

    private static function _validate_set_data($transaction_id, $cust_id, $gateway, $SMSData, $error_codes) {
        //Check if Transaction ID is set else generate One
        if ($transaction_id == '') {
            $transaction_id = new \sms\Utils\Utils();
            self::$transaction_id = $transaction_id->get_uwd('8', 'SMS');
        } else {
            self::$transaction_id = $transaction_id;
        }

        if ($cust_id != '' AND ! is_numeric($cust_id))
            \sms\Error\Error::set_error('Invalid Customer ID Provided');


        if ($gateway == '')
            \sms\Error\Error::set_error('Invalid Gateway Name Provided');

        if (!is_array($SMSData)) {
            \sms\Error\Error::set_error('Invalid Initial Data Provided');
        }

        //Check for Errors before Arranging the Data
        if (!\sms\Error\Error::check_error())
            return;

        //Arrange the Data
        self::_arrange_insert_data($cust_id, $gateway, $SMSData, $error_codes);
    }

    /*
     * Private Function to Arrange the Data and put it to place
     * 
     */

    private static function _arrange_insert_data($cust_id, $gateway, $SMSData, $error_codes) {
        /*
          | The SMS Data is Inserted in two Tables
          |     mod_sms_db      Main Data of the SMS
          |     mod_sms_status  Status of the SMS
         */

        //Check if is Error Message or Success
        if (!isset($SMSData['response']['message_data'])) {
            //There is some error, Prepare the Data as per the Error Status
            self::$data['mod_sms_db'][] = [
                'trans_id' => self::$transaction_id,
                'cust_id' => $cust_id,
                'text' => $SMSData['msg'],
                'to' => '',
                'gateway' => strtoupper($gateway),
                'utf8' => $SMSData['unicode'],
                'flash' => $SMSData['fl'],
                'gateway_trans_id' => '',
                'gateway_batch_id' => '',
                'datetime' => date('Y-m-d H:i:s'),
                'transtime' => ((isset($SMSData['schedule'])) ? $SMSData['schedule'] : date('Y-m-d H:i:s')),
                'senderid' => $SMSData['sid'],
                'request' => $SMSData['request'][0],
                'response' => json_encode($SMSData['response']),
                'rel_id' => self::$transaction_id,
                'sms_status' => 00,
                'status' => 1
            ];

            self::$data['mod_sms_status'][] = [
                'trans_id' => self::$transaction_id,
                'gateway_trans_id' => '',
                'datetime' => date('Y-m-d H:i:s'),
                'gateway_response' => $SMSData['response']['errorcode'],
                'resp_status' => ((array_key_exists($SMSData['response']['errorcode'], $error_codes)) ? $SMSData['response']['errorcode'] : 0),
                'status' => 1
            ];

            return;
        }

        foreach ($SMSData['response']['message_data'] AS $keym => $valm) {
            //Generate Gateway Batch ID
            $batch_id = new \sms\Utils\Utils();
            $batch_id = $batch_id->get_uwd('8', 'SMSBTCH');
            self::$data['mod_sms_batch'][] = [
                'batch_ref_no' => $batch_id,
                'gateway_batch_id' => @$SMSData['response']['message_data'][$keym][0]['message_id'],
                'request' => $SMSData['request'][$keym],
                'response' => json_encode($SMSData['raw_response'][$keym]),
                'error_code' => @$SMSData['response']['message_data'][$keym][0]['errorcode'],
                'error_message' => @$SMSData['response']['message_data'][$keym][0]['message'],
                'status' => 1
            ];

            //Each SMS are in Nested Inside, Iterate again 
            foreach ($valm AS $key => $val) {
                //Generate an Internal Transaction ID for Each SMS
                if ($keym == 0 AND $key == 0) {
                    $transaction_id = self::$transaction_id;
                } else {
                    $transaction_id = new \sms\Utils\Utils();
                    $transaction_id = $transaction_id->get_uwd('8', 'SMS');
                }


                //Prepare the Data Array
                self::$data['mod_sms_db'][] = [
                    'trans_id' => $transaction_id,
                    'cust_id' => $cust_id,
                    'text' => $val['text'],
                    'to' => $val['number'],
                    'gateway' => strtoupper($gateway),
                    'utf8' => $SMSData['dc'],
                    'flash' => $SMSData['fl'],
                    'gateway_trans_id' => $val['message_id'],
                    'batch_ref_no' => $batch_id,
                    'datetime' => date('Y-m-d H:i:s'),
                    'transtime' => ((isset($SMSData['schedule'])) ? $SMSData['schedule'] : date('Y-m-d H:i:s')),
                    'senderid' => $SMSData['sid'],
                    'rel_id' => self::$transaction_id,
                    'sms_status' => 10,
                    'status' => 1
                ];

                self::$data['mod_sms_status'][] = [
                    'trans_id' => $transaction_id,
                    'gateway_trans_id' => $val['message_id'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'gateway_response' => $val['errorcode'],
                    'resp_status' => ((array_key_exists($val['errorcode'], $error_codes)) ? $val['errorcode'] : '000'),
                    'status' => 1
                ];
            }
        }
    }

    /*
      |------------------------------------------------------------------------------
      |  Update Functions
      |------------------------------------------------------------------------------
      |
     */

    /*
     * 
     */
}
