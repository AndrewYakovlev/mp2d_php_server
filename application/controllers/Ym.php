<?php

class Ym extends CI_Controller
{
    public $base_url;
    public $app_id;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('account_model');
        $this->base_url = 'https://api.partner.market.yandex.ru/v2/';
        $this->app_id = '45813d304d4442f2b1f754aea38a56c1';
    }

    public function getData()
    {
        $accounts = $this->getAccounts();
        if ($accounts) {
            foreach ($accounts as $account) {
                echo json_encode($this->getOrders($account->token, $account->client_id));
            }
        }
    }

    public function getAccounts()
    {
        return $this->account_model->get_all(['type' => 'ym', 'active' => true]);
    }

    public function getOrders($token, $campaignId, $page = 1)
    {
        $path = 'campaigns/' . $campaignId . '/orders.json?page='.$page;
        $params = [
            'http' => [
                'method' => 'GET',
                'header' =>
                    'Content-Type: application/json' . PHP_EOL .
                    'Authorization: OAuth oauth_token="' . $token . '", oauth_client_id="' . $this->app_id . '"'
            ]
        ];

        $context = stream_context_create($params);
        $response = file_get_contents(
            $this->base_url . $path,
            false,
            $context);

        return json_decode($response);
    }

}