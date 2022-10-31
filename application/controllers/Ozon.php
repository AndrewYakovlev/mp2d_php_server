<?php

class Ozon extends CI_Controller
{
    public $base_url;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('account_model');
        $this->base_url = 'https://api-seller.ozon.ru';
    }

    public function getData(){
        $accounts = $this->getAccounts();
        if($accounts){
            foreach($accounts as $account){
                echo json_encode($this->getOrders($account->token, $account->client_id));
            }
        }
    }

    public function getAccounts()
    {
        return $this->account_model->get_all(['type' => 'ozon', 'active' => true]);
    }

    public function getProductList(){
        $path  = '/v2/product/list';
    }

    public function getOrders($token, $clientId){

        $path  = '/v3/posting/fbs/list';


        $request = new stdClass;
        $request->dir = 'DESC';
        $request->filter = new stdClass;
        $request->filter->since = "2022-10-01T00:00:00.000Z";
        $request->filter->to = "2022-11-01T23:59:59.000Z";
        $request->limit = 100;
        $request->offset = 0;
        // $request->with = new stdClass;
        // $request->with->analytics_data = true;
        // $request->with->barcodes = true;
        // $request->with->financial_data = true;

        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Client-Id: '.$clientId. PHP_EOL .
                    'Api-Key: '.$token. PHP_EOL .
                    'Content-Type: application/json',
                'content' => json_encode($request),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents(
            $this->base_url.$path,
            false,
            $context);
        return json_decode($response);
    }
}