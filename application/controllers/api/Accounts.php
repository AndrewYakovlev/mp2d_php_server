<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Accounts extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('account_model');
    }

    public function get()
    {
        $filter = [];
        if ($this->input->get('type') != null) $filter['type'] = $this->input->get('type');
        $accounts = [];
        $result = $this->account_model->get_all($filter);
        if ($result) {
            foreach ($result as $row) {
                $row->active = (bool)$row->active;
                $accounts[] = $row;
            }
        }
        echo json_encode($accounts);
    }

    public function save()
    {

        $data = json_decode($this->input->raw_input_stream);

        if ($data->type == 'ym') {
            $params = array(
                'grant_type' => 'authorization_code',
                'code' => $data->code,
                'client_id' => '45813d304d4442f2b1f754aea38a56c1',
                'client_secret' => '83f3b56d69c3493ab31d80bbec007951',
            );
            $ch = curl_init('https://oauth.yandex.ru/token');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $yd = curl_exec($ch);
            curl_close($ch);
            $yd = json_decode($yd );
            if (isset($yd->access_token)) {
                unset($data->code);
                $data->token = $yd->access_token;
            }else{
                echo json_encode('error');
                return;
            }
        }

        if (isset($data->id)) {
            $id = $data->id;
            unset($data->id);
            $this->account_model->update($data, ['id' => $id]);
        } else {
            $data->id = $this->account_model->insert($data);
        }
        echo json_encode($data);

    }

    public function remove($id = null)
    {
        if ($id != null) {
            $this->account_model->delete($id);
        }
    }


}