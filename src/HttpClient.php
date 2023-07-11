<?php

namespace Captainstdin\GatewayGoClient;

class HttpClient {
    private $url;


    
    public function __construct(string  $url) {
        $this->url = $url;
    }
    
    public function SendRequest($data) {
        $ch = \curl_init($this->url);

        \curl_setopt($ch, CURLOPT_POST, 1);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HEADER, false); // 禁止输出header
        \curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 设置超时时间为10秒
        $response = curl_exec($ch);

        \curl_close($ch);
        
        return $response;
    }
}


?>