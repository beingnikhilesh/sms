<?php

namespace beingnikhilesh\sms\Provider;

use beingnikhilesh\sms\Format\Smppsmshub;

class Smpp extends Smppsmshub{
    /**
     * BASE Production URL of the SMS Company
     */
    protected $push_url = 'http://182.18.143.11/api/mt/SendSMS?'; 
    
    /**
     * Routes of SMPPSMShub
     */
    protected $trans_route = 15;
    protected $promo_route = 15;
    
    /**
     * Error Code Matching as per the Standard Response Codes
     */
    protected $error_reponse = [
        '000' => '000',
        '001' => '001',
        '003' => '002',
        '004' => '003',
        '005' => '015',
        '006' => '0',
        '007' => '001',
        '008' => '004',
        '009' => '005',
        '010' => '006',
        '011' => '007',
        '012' => '017',
        '013' => '018',
        '014' => '005',
        '015' => '002',
        '017' => '010',
        '018' => '011',
        '019' => '012',
        '020' => '013',
        '021' => '009',
        '022' => '014',
        '023' => '015',
        '024' => '016',
        '025' => '019',
        '026' => '020',
        '027' => '021'
    ];
    
    /**
     * Maximum Recipients in a Single API Call
     */
    protected $max_send_numbers = 90;
}

