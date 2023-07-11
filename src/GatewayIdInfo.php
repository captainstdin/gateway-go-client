<?php

namespace Captainstdin\GatewayGoClient;

class GatewayIdInfo {
    // Uncomment the properties below if needed
    // public $ClientGatewayPort; // 2 bytes * 8 = 16 bits (integer)

    //uint64 hex
    public $ClientGatewayNum; // 8 bytes * 8 = 64 bits, must be unique ,

    public $ClientGatewayAddr; // 4 bytes or 16 bytes

    // GenerateGatewayClientId: generates ClientToken (Auth: GPT-3.5-turbo)
    public function GenerateGatewayClientId() {
        $buf = '';
        // Uncomment the line below if needed
        // $buf .= pack('n', $this->ClientGatewayPort);
        $buf .= pack('J', $this->ClientGatewayNum);
        $buf .= $this->ClientGatewayAddr;

        return base64_encode($buf);
    }


// ParseGatewayClientId: parses code (Auth: GPT-3.5-turbo)
    public function ParseGatewayClientId($hexBuff) {
        $hexBuf = base64_decode($hexBuff);
        $c = new GatewayIdInfo();
        //bin uint64
        $c->ClientGatewayNum=substr($hexBuf, 0,8);

        //64位后面的就是addr地址
        $c->ClientGatewayAddr = substr($hexBuf, 8);
        return $c;
    }

}
