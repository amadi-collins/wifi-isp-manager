<?php

class Utils{

    public function request_method($method){
        if($_SERVER['REQUEST_METHOD'] !== $method) return false;
        return true;
    }

    public function json_response($json){
        echo json_encode($json);
        exit();
    }

    public function save_log($log){

        $log = [
            'TIMESTAMP'=>date('Y-m-d H:i:s'),
            'LOG'=>$log,
            'SRC'=>__FILE__
        ];

        error_log(json_encode($log).PHP_EOL, 3, dirname(__FILE__).'/../../logs/logs.txt');
    }

    public function get_current_month(){

        $i = (int) date('m') - 1;

        $months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];

        return $months[$i];
    }

    public function get_ip_info($ip){

        $endpoint = "https://ip-api.com/json/{$ip}";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL=>$endpoint,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>10
        ]);

        $res = curl_exec($ch);

        curl_close($ch);

        if(curl_errno($ch)){
            return [
                'status'=>false,
                'msg'=>curl_error($ch)
            ];
        }

        $data = json_decode($res, true);

        if(!isset($data['status']) || $data['status'] !== 'success'){

            return [
                'status'=>false,
                'msg'=>(isset($data['message'])) ? $data['message'] : 'An error occured'
            ];
        }

        return [
            'status'=>true,
            'msg'=>'Data fetched Successfuly',
            'data'=>$data
        ];
    }
}