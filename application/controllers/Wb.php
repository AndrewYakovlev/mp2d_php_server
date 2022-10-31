<?php

class Wb extends CI_Controller
{
    public $base_url;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('account_model');
        $this->base_url = 'https://suppliers-api.wildberries.ru/api/v2/';
    }

    public function getData(){
        $accounts = $this->getAccounts();
        if($accounts){
            foreach($accounts as $account){
                //echo json_encode($this->getInfo($account->token)); // get products
                //echo json_encode($this->getStocks($account->token)); // get stock
                echo json_encode($this->getSuppliesOrders($account->token));
            }
        }
    }

    public function getAccounts()
    {
        return $this->account_model->get_all(['type' => 'wb', 'active' => true]);
    }

    public function getInfo($token)
    {
        $url = 'https://suppliers-api.wildberries.ru/public/api/v1/info';
        $params = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: ' . $token,
            ]
        ];
        $query = [];
        $context = stream_context_create($params);
        $response = file_get_contents(
            $url . '?' . http_build_query($query),
            false,
            $context);
        return json_decode($response);
    }

    public function getStocks($token)
    {
        $path = 'stocks';
        $params = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: ' . $token,
            ]
        ];

        $query = [
            'skip' => 0,
            'take' => 100
        ];

        $context = stream_context_create($params);
        //ON_DELIVERY / ACTIVE
        $response = file_get_contents(
            $this->base_url . $path . '?' . http_build_query($query),
            false,
            $context);
        return json_decode($response);
    }

    public function getSupplies($token)
    {
        $path = 'supplies';
        $params = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: ' . $token,
            ]
        ];

        $context = stream_context_create($params);
        //ON_DELIVERY / ACTIVE
        $response = file_get_contents(
            $this->base_url . $path . '?' . http_build_query(['status' => 'ON_DELIVERY']),
            false,
            $context);

        return json_decode($response)->supplies;
    }

    public function getSuppliesOrders($token)
    {
        $supplies = $this->getSupplies($token);
        if (is_array($supplies) && count($supplies) > 0) {
            foreach ($supplies as $supplier) {

                $path = 'orders';
                $params = [
                    'http' => [
                        'method' => 'GET',
                        'header' => 'Authorization: ' . $token,
                    ]
                ];

                $context = stream_context_create($params);

                $response = file_get_contents(
                    $this->base_url . "supplies/{$supplier->supplyId}/" . $path,
                    false,
                    $context);

                $response = json_decode($response);
                if (isset($response->orders) && count($response->orders) > 0) {
                    foreach ($response->orders as $order) {
                        $this->getStickers($token, $order->orderId);
                    }
                }

            }
        }
    }

    public function getStickers($token, $id){
        $url = 'https://suppliers-api.wildberries.ru/api/v2/orders/stickers/pdf';
        $orderIds = [
            'orderIds' => [(int)$id]
        ];
        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Authorization: '.$token. PHP_EOL .
                    'Content-Type: application/json',
                'content' => json_encode($orderIds),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents(
            $url,
            false,
            $context);

        $response = json_decode($response);
        if(!$response->error){
            file_put_contents('./files/stickers/wb/'.$id.'.pdf', base64_decode($response->data->file));
        }
    }
}