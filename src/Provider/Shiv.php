<?php

namespace beingnikhilesh\sms\Provider;

use beingnikhilesh\sms\Format\Serialised;

class Shiv extends Serialised{
    /**
     * BASE Production URL of the SMS Company
     */
    protected $push_url = 'http://5.189.153.48:8080/vendorsms/pushsms.aspx?'; 
    
    /**
     * Error Code Matching as per the Standard Response Codes
     */
    protected $error_reponse = [
        '0' => '000',
        '1' => '001',
        '2' => '001',
        '3' => '002',
        '4' => '003',
        '5' => '015',
        '6' => '006',
        '7' => '001',
        '8' => '004',
        '9' => '005',
        '10' => '006',
        '11' => '007',
        '12' => '017',
        '13' => '018',
        '14' => '005',
        '15' => '002',
        '16' => '008',
        '17' => '010',
        '18' => '011',
        '19' => '012',
        '20' => '013',
        '21' => '013',
        '22' => '014',
        '23' => '015',
        '24' => '016'
    ];
}

