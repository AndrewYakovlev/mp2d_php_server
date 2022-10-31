<?php

class Account_model extends MY_Model
{
    public $table = 'accounts';

    public function __construct()
    {
        parent::__construct();
        $this->return_as = 'object';
    }
}