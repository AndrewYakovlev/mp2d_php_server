<?php

class Sber extends CI_Controller
{
    public $base_url;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('account_model');
        $this->base_url = 'https://partner.goodsteam.tech/api/market/v1/orderService/';
    }

    public function getData()
    {
        $accounts = $this->getAccounts();
        if ($accounts) {
            foreach ($accounts as $account) {
                echo json_encode($this->getOrders($account->token));
            }
        }
    }

    public function getAccounts()
    {
        return $this->account_model->get_all(['type' => 'sber', 'active' => true]);
    }

    public function getOrders($token)
    {
        $path = 'order/search';

        $request = new stdClass;
        $request->data = new stdClass;
        $request->data->token = $token;
        $request->meta = new stdClass;


        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($request),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents($this->base_url . $path, false, $context);

        return  json_decode($response);
    }

    public function getOrder($id)
    {

    }
}