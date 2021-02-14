<?php

namespace beingnikhilesh\sms\Provider;

interface Provider
{
    public function send(\sms\SMSBuilder $object);
    
    public function status();
    
    public function balance();
}

