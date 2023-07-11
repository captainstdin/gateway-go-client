<?php

namespace Captainstdin\GatewayGoClient;

/**
 * Author : gpt-3.5-turbo
 */
class protocol
{
    public $PackageLen; // 4字节 包体长度,不参与签名
    public $Sign; // 不参与签名
    public $TimeStamp; // 8字节
    public $Cmd; // 4字节的指令
    public $Json; // 不确定

    public function ToString()
    {
        return $this->ToByte();
    }

    public function sumPackageLen($ExcludeJson)
    {
        // 计算二进制长度
        $b2 = '';
        $b2 .= pack('N', $this->TimeStamp);
        $b2 .= $this->Sign;

        if (!$ExcludeJson) {
            $b2 .= $this->Json;
        }

        $b2 .= pack('N', $this->Cmd);

        return strlen($b2);
    }

    // 生成通讯字节
    public function ToByte()
    {
        $this->PackageLen = $this->sumPackageLen(false);
        // 4字节的包头 + 16字节的签名 + 8字节的unix时间戳(int64) + 2字节的指令 + n字节的json字符串
        $b = '';
        $b .= pack('N', $this->PackageLen); // 4字节的包头
        $b .= $this->Sign; // 16字节的签名
        $b .= pack('N', $this->TimeStamp); // 8字节的unix时间戳(int64)
        $b .= pack('N', $this->Cmd); // 4字节的指令
        $b .= $this->Json; // n字节的json字符串

        return $b;
    }

    public function sumSign($secretKey)
    {
        // []byte(sign签名) = [8]byte(timeUnix)+[2]byte(Cmd)+[n]byte(json)+私钥
        $ToBeSign = '';
        // 时间戳
        $ToBeSign .= pack('N', $this->TimeStamp); // [8]byte(timeUnix)
        // 指令
        $ToBeSign .= pack('N', $this->Cmd); // [4]byte(Cmd)
        // json内容
        $ToBeSign .= $this->Json; // [n]byte(json)
        $ToBeSign .= $secretKey;

        return md5($ToBeSign, true);
    }
}

function toString($v)
{
    switch (gettype($v)) {
        case 'array':
            $marshal = json_encode($v);
            if ($marshal === false) {
                return '';
            }
            return $marshal;
        case 'string':
            return $v;
        case 'integer':
            return strval($v);
        default:
            return '';
    }
}

function GenerateSignTimeByte($Cmd, $data, $secretKey, $funcTime)
{
    $jsonDataStr = json_encode($data);
    if ($jsonDataStr === false) {
        return null;
    }

    $expireTime = time() + $funcTime();

    $gen = new GenerateComponentSign();
    $gen->TimeStamp = $expireTime;
    $gen->Cmd = $Cmd;
    $gen->Json = $jsonDataStr;

    $gen->Sign = $gen->sumSign($secretKey);
    return $gen;
}

function ParseAndVerifySignJsonTime($dataByte, $secretKey)
{
    $gen = new GenerateComponentSign();
    $reader = new \stdClass();
    $reader->data = $dataByte;
    $reader->pos = 0;

    $minLen = $gen->sumPackageLen(true);
    if (strlen($reader->data) <= $minLen) {
        return null;
    }

    // 读取包头长度
    $gen->PackageLen = unpack('N', substr($reader->data, $reader->pos, 4))[1];
    $reader->pos += 4;

    // 读取签名
    $gen->Sign = substr($reader->data, $reader->pos, 16);
    $reader->pos += 16;

    // 读取时间戳
    $gen->TimeStamp = unpack('N', substr($reader->data, $reader->pos, 8))[1];
    $reader->pos += 8;

    // 读取指令
    $gen->Cmd = unpack('N', substr($reader->data, $reader->pos, 4))[1];
    $reader->pos += 4;

    // 读取JSON数据, jsonLen为json长度应该是gen.PackageLen - (时间+签名+指令)
    $jsonLen = $gen->PackageLen - $gen->sumPackageLen(false);
    $gen->Json = substr($reader->data, $reader->pos, $jsonLen);
    $reader->pos += $jsonLen;

    if ($gen->Sign !== $gen->sumSign($secretKey)) {
        return null;
    }

    if ($gen->TimeStamp <= time()) {
        return null;
    }

    return $gen;
}