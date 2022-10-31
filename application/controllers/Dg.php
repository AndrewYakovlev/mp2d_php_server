<?php

class Dg extends CI_Controller
{
    public $base_url;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('account_model');
        $this->base_url = 'https://api.dostavka.guru/client/';
    }

    public function getAccounts()
    {
        return $this->account_model->get_all(['type' => 'dg', 'active' => true]);
    }

    public function getData()
    {
        $accounts = $this->getAccounts();
        if ($accounts) {
            foreach ($accounts as $account) {
                echo json_encode($this->getStockReport($account->token, $account->partner_id));
            }
        }
    }

    public function createOrder($token, $partner_id, $order = null)
    {
        $path = 'https://api.dostavka.guru/client/in_up_market.php?json=yes';

        $request = new stdClass();

        $request->partner_id = $partner_id;
        $request->key = $token;
        $request->usluga = 'ДОСТАВКА'; //если нужно доставить груз в маркет плейс, то ДОСТАВКА, если забрать из маркетплейса, то ВОЗВРАТ
        $request->marketplace_id = '49002'; //идентификатор склада маркетплейса, куда нужно доставить поставку, может принимать значение из справочника Маркетплейс справочник
        $request->sposob_dostavki = 'Маркетплейс'; //должно равняться значению Маркетплейс.
        $request->tip_otpr = 'FBS с комплектацией'; //ип отправления, может принимать следующие значения: FBO | FBS | FBO с комплектацией | FBS с комплектацией
        $request->docs_return = 'Y'; //Требуется возврат документов (передается, только если требуется возврат документов)
        $request->order_number = '12345'; //номер заказа, необязательное поле, если оно не было указано, то система присвоит ему уникальное значение.
        $request->cont_name = 'Name'; // сотрудник на стороне магазина, отвечающий за процесс поставки грузов на маркетплейс, который может решить вопросы возникающие в ходе доставки
        $request->cont_phone = '+79990001122'; // контактный телефон сотрудника занимающегося вопросами доставки в макркетплейс
        $request->cont_mail = 'name@domain.com'; //адерс электронной почты, на который прийдет информация о поставке для заказа пропуска на территорию маркетплейса
        $request->date_dost = Date('Y.m.d', time()+60*60*24);// планируемая дата передачи ТМЦ на склад маркетплейса, если по заказу требуется сборка, то нужно закладывать время на сборку в соответствии с соглашением магазина
        $request->region_iz = 'Москва'; //регион доставки в маркетплейс
        $request->ocen_sum = 200; // сумма стоимостей строк заказа
        $request->picking = 'Y'; // если требуется собирать поставку со склада ответственного хранения
        $request->free_date = '1'; //Требуется для указания ближайшей даты доставки если на выбранную дату, доставки нет. Может иметь значение 1 - Автоподставлять. 2. Не изменять

        //перечень вложений, которые нужно собрать и/или передать на склад маркетплейса
        $request->products = [];

        $product = new stdClass;
        $product->code = '43656'; //артикул единицы складского учета
        $product->name = 'Куртка мужская Krona, синяя, размер 54'; // - наименование единицы складского учета
        $product->bare = '1000002055724'; // - штрихкод единицы складского учета
        $product->ed = 'шт'; // - единица измерения, используемая для
        $product->qty = 1; // - количество единиц измерения ТМЦ, которые составляют поставку
        $product->oc = 200; //- оценочная стоимость единицы складского учета
        $product->mono = 0; // - признак запрета соседства с другими артикулами в одной упаковке
        $product->pack = 1; //- признак использования упаковки магазина
        $product->mark = ''; //- признак требования перемаркировки ТМЦ перед отправкой в маркетплейс
        $product->outnumber = ''; //- значение штрихкода, которое должно быть на стикере для маркетплейса
        $product->serialcode = ''; //- значение артикула, которое должно быть на стикере для маркетплейса
        $product->brief = ''; // - Описание единицы складского учета, которое должно быть на стикере для маркетплейса
        $product->note = ''; //- примечание, как правило исплользуется для пояснения на сборку заказа

        // Добавляем продукт к заказку, если несколько позиций - пробегаемся в цикле
        $request->products[] = $product;


        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($request),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents(
            $path,
            false,
            $context);
        return json_decode($response);
    }

    public function getStocks($token, $partner_id)
    {
        $path = 'https://api.dostavka.guru/methods/stocks/';
        $request = new stdClass();
        $request->partner_id = $partner_id;
        $request->key = $token;

        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($request),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents(
            $path,
            false,
            $context);
        return json_decode($response);
    }


    public function getMarketplaces($token, $partner_id)
    {
        $path = 'marketplaces_list.php?json=yes';
        $request = new stdClass();
        $request->partner_id = $partner_id;
        $request->key = $token;

        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($request),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents(
            $this->base_url . $path,
            false,
            $context);
        return json_decode($response);
    }

    public function getStockReport($token, $partner_id)
    {
        $path = 'https://api.dostavka.guru/methods/stockReport/';
        $request = new stdClass();
        $request->partner_id = $partner_id;
        $request->key = $token;
        $request->date_filter_start = '2022-01-01';
        $request->date_filter_end = Date('Y-m-d');
        $params = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($request),
            ]
        ];

        $context = stream_context_create($params);

        $response = file_get_contents(
            $path,
            false,
            $context);
        return json_decode($response);
    }

}