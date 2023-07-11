<?php

namespace Captainstdin\GatewayGoClient;

require_once "Consts.php";

class Gateway implements GatewayLibInterface
{

    public function getPrintGatewayAddr()
    {
        print_r($this->gatewayAddr);
    }


    private $signKey = '';
    private $registerAddr = [];

    private $gatewayAddr = [];

    public function __construct($register = [], $signKey = '')
    {

        $this->registerAddr=$register;
        $this->signKey = $signKey;
        $gatewayApis = [];

        foreach ($this->registerAddr as $r) {
            $httpC = new  HttpClient($r . RegisterForComponent);

            $dataSign = Protocol::GenerateSignTimeByte(CommandComponentAuthRequest, [
                "component_type" => 1,
                "name" => "sdk-client",
                "protocol_public_gateway_connection_info" => [],
                "Data" => "sdk auth data",
                "authed" => "0"
            ], $signKey);

            $res = $httpC->SendRequest($dataSign->ToByte());

            if ($res === false) {
                continue;
            }

            $res_data = json_decode($res, true);

            if (isset($res_data['errCode']) && $res_data['errCode'] != 200) {
                print_r($res_data['errMsg']);
                continue;
            }

            if (!isset($res_data['data']) || !is_array($res_data['data'])) {
                continue;
            }

            foreach ($res_data['data'] as $gatewayAddr) {
                if (!isset($gatewayAddr['gateway_addr'])) {
                    continue;
                }
                $gatewayApis[] = $gatewayAddr['gateway_addr'];
            }
        }

        $this->gatewayAddr=\array_unique($gatewayApis);
    }


    private function distributed(string $signData): array
    {
        $arrResult = [];
        foreach ($this->gatewayAddr as $g) {
            $httpC = new  HttpClient($g . RegisterForComponent);

            $arrResult[] = $httpC->SendRequest($signData);
        }

        return $arrResult;
    }

    public function sendToAll(string $data, array $client_id_array, array $exclude_client_id): void
    {
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandSendToAll, [
            'Data' => $data,
            'client_id_array' => $client_id_array,
            'exclude_client_id' => $exclude_client_id,
        ], $this->signKey);

        $this->distributed($dataSign->ToByte());
    }

    public function sendToClient(string $client_id, string $send_data)
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandSendToClient, [
            'client_id' => $client_id,
            'send_data' => $send_data
        ], $this->signKey);

        $sdk->SendRequest($dataSign->ToByte());
    }

    public function closeClient(string $client_id)
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandCloseClient, [
            'client_id' => $client_id,
        ], $this->signKey);

        $sdk->SendRequest($dataSign->ToByte());
    }

    public function isOnline(string $client_id): int
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandIsOnline, [
            'client_id' => $client_id,
        ], $this->signKey);

        $res = $sdk->SendRequest($dataSign->ToByte());

        $Data = json_decode($res, true);
        if (isset($Data['isOnline'])) {
            print_r("error isOnline()  : ", $res);
            return 0;
        }
        return $Data['isOnline'];
    }

    public function bindUid(string $client_id, string $uid): void
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandBindUid, [
            'client_id' => $client_id,
            'uid' => $uid,
        ], $this->signKey);
        $sdk->SendRequest($dataSign->ToByte());
    }

    public function unbindUid(string $client_id, string $uid): void
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandUnbindUid, [
            'client_id' => $client_id,
            'uid' => $uid,
        ], $this->signKey);
        $sdk->SendRequest($dataSign->ToByte());
    }

    public function isUidOnline(string $uid): int
    {
        $ret = 0;
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandIsUidOnline, [
            'uid' => $uid,
        ], $this->signKey);
        $resArray = $this->distributed($dataSign->ToByte());
        foreach ($resArray as $item) {
            $Data = json_decode($item, true);
            if (isset($Data['isUidOnline'])) {
                print_r("error isUidOnline()  : ", $item);
                continue;
            }
            if ($Data['isUidOnline'] == 1) {
                $ret = 1;
            }
        }
        return $ret;
    }

    public function getClientIdByUid(string $uid): array
    {
        $ret = [];
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandGetClientIdByUid, [
            'uid' => $uid,
        ], $this->signKey);
        $resArr = $this->distributed($dataSign->ToByte());
        foreach ($resArr as $item) {
            $Data = json_decode($item, true);
            if (isset($Data['clientIDList'])) {
                print_r("error getClientIdByUid()  : ", $item);
                continue;
            }
            if (!is_array($Data['clientIDList'])) {
                print_r("error2 getClientIdByUid()  : ", $item);
                continue;
            }
            $ret = array_merge($ret, $Data['clientIDList']);
        }
        //去重，但是不可能有重复client_id， gatewayip是唯一的，gatewayNum也是他自己的map Lock
        //$uniqueArray = array_unique($ret);
        return $ret;
    }

    public function getUidByClientId(string $client_id): ?string
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandGetUidByClientId, [
            'client_id' => $client_id,
        ], $this->signKey);
        $res = $sdk->SendRequest($dataSign->ToByte());

        $Data = json_decode($res, true);
        if (!isset($Data['uid'])) {
            print_r("error2  getUidByClientId()  : ", $res);
            return null;
        }

        return $Data['uid'];
    }

    public function sendToUid(string $uid, string $message): void
    {
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandSendToUid, [
            'uid' => $uid,
            'message' => $message
        ], $this->signKey);

        $this->distributed($dataSign->ToByte());
    }

    public function joinGroup(string $client_id, string $group): void
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandJoinGroup, [
            'client_id' => $client_id,
            'group' => $group,
        ], $this->signKey);
        $sdk->SendRequest($dataSign->ToByte());
    }

    public function leaveGroup(string $client_id, string $group)
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);

        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandLeaveGroup, [
            'client_id' => $client_id,
            'group' => $group,
        ], $this->signKey);
        $sdk->SendRequest($dataSign->ToByte());
    }

    public function ungroup(string $group)
    {
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandUngroup, [
            'group' => $group,
        ], $this->signKey);
        $this->distributed($dataSign->ToByte());
    }

    public function sendToGroup(string $group, string $message, array $exclude_client_id)
    {
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandSendToGroup, [
            'group' => $group,
            'message' => $message,
            'exclude_client_id' => $exclude_client_id,
        ], $this->signKey);
        $this->distributed($dataSign->ToByte());
    }

    public function getClientIdCountByGroup(string $group): int
    {
        $count = 0;
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandGetClientIdCountByGroup, [
            'group' => $group,
        ], $this->signKey);
        $resArr = $this->distributed($dataSign->ToByte());

        foreach ($resArr as $item) {
            $Data = json_decode($item, true);
            if (!isset ($Data['clientCount'])) {
                print_r("warning skip  getClientIdCountByGroup()  : ", $item);
                continue;
            }
            $count += $Data['clientCount'];
        }
        return $count;
    }

    /**
     * @param string $group
     * @return array
    array(
    '7f00000108fc00000008' => array(...),
    '7f00000108fc00000009' => array(...),
    )
     */
    public function getClientSessionsByGroup(string $group): array
    {
        $clientSessions = [];
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandGetClientSessionsByGroup, [
            'group' => $group,
        ], $this->signKey);
        $resArr = $this->distributed($dataSign->ToByte());

        foreach ($resArr as $item) {
            $Data = json_decode($item, true);
            //格式不对则跳过
            if (!isset ($Data['clientSessions']) || !is_array($Data['clientSessions'])) {
                print_r("warning skip  getClientSessionsByGroup()  : ", $item);
                continue;
            }

            foreach ($Data['clientSessions'] as $client_id=>$arr){
                if (!isset($clientSessions[$client_id])){
                    $clientSessions[$client_id]=[];
                }
                $clientSessions[$client_id]=array_merge($clientSessions[$client_id],$arr);
            }
        }
        return $clientSessions;
    }

    public function getAllClientIdCount(): int
    {
        $count = 0;
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandGetAllClientIdCount, [
        ], $this->signKey);
        $resArr = $this->distributed($dataSign->ToByte());

        foreach ($resArr as $item) {
            $Data = json_decode($item, true);
            //格式不对则跳过
            if (!isset ($Data['clientCount']) ) {
                print_r("warning skip  getAllClientIdCount()  : ", $item);
                continue;
            }
            $count+=$Data['clientCount'];
        }
        return $count;
    }

    public function getAllClientSessions(): array
    {
        $clientSessions = [];
        $dataSign = Protocol::GenerateSignTimeByte(GatewayCommandGetAllClientSessions, [
        ], $this->signKey);
        $resArr = $this->distributed($dataSign->ToByte());

        foreach ($resArr as $item) {
            $Data = json_decode($item, true);
            //格式不对则跳过
            if (!isset ($Data['clientSessions']) || !is_array($Data['clientSessions'])) {
                print_r("warning skip  getAllClientSessions()  : ", $item);
                continue;
            }

            foreach ($Data['clientSessions'] as $client_id=>$arr){
                if (!isset($clientSessions[$client_id])){
                    $clientSessions[$client_id]=[];
                }
                $clientSessions[$client_id]=array_merge($clientSessions[$client_id],$arr);
            }
        }
        return $clientSessions;
    }

    public function setSession(string $client_id, array $data):void
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandSetSession, [
            'client_id' => $client_id,
            'Data'=>$data
        ], $this->signKey);
        $sdk->SendRequest($dataSign->ToByte());
    }

    public function updateSession(string $client_id, array $data)
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandUpdateSession, [
            'client_id' => $client_id,
            'Data'=>$data
        ], $this->signKey);
        $sdk->SendRequest($dataSign->ToByte());
    }

    public function getSession(string $client_id): array
    {
        $clientInfo = (new GatewayIdInfo())->ParseGatewayClientId($client_id);
        $sdk = new HttpClient($clientInfo->ClientGatewayAddr . GatewayForSdkPath);
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetSession, [
            'client_id' => $client_id,
        ], $this->signKey);
        $res=$sdk->SendRequest($dataSign->ToByte());

        $Data=json_decode($res,true);
        if (!isset($Data['session'])){
            print_r("warning skip  getSession()  : ", $res);
            return [];
        }
        return $Data['session'];
    }

    /**
     * @param string $group
     * @return array
     *
     * array(
    '7f00000108fc00000008' => '7f00000108fc00000008',
    '7f00000108fc00000009' => '7f00000108fc00000009'
    )
     */
    public function getClientIdListByGroup(string $group): array
    {

        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetClientIdListByGroup, [
            'group' => $group,
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());
        $client_list=[];
        foreach ($retArr as $item){
            $Data=json_decode($item,true);
            if (!isset($Data['clientIDList'])){
                print_r("warning skip  getClientIdListByGroup()  : ", $item);
                continue;
            }
            foreach ($Data['clientIDList'] as $client_id){
                $client_list[$client_id]=$client_id;
            }
        }
        return $client_list;
    }

    /**
     * @return array
     * array(
    '7f00000108fc00000008' => '7f00000108fc00000008',
    '7f00000108fc00000009' => '7f00000108fc00000009'
    )
     */
    public function getAllClientIdList(): array
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetAllClientIdList, [
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());

        $client_list=[];
        foreach ($retArr as $item){
            $Data=json_decode($item,true);

            if (!isset($Data['clientIDList'])){
                print_r("warning skip  getAllClientIdList()  : ", $item);
                continue;
            }
            foreach ($Data['clientIDList'] as $client_id){
                $client_list[$client_id]=$client_id;
            }
        }
        return $client_list;
    }

    public function getUidListByGroup(string $group): array
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetUidListByGroup, [
            'group'=>$group,
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());

        $client_list=[];
        foreach ($retArr as $item){
            $Data=json_decode($item,true);

            if (!isset($Data['uidList'])){
                print_r("warning skip  getAllClientIdList()  : ", $item);
                continue;
            }
            foreach ($Data['uidList'] as $client_id){
                $client_list[$client_id]=$client_id;
            }
        }
        return $client_list;
    }

    public function getUidCountByGroup(string $group): int
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetUidCountByGroup, [
            'group'=>$group,
        ], $this->signKey);


        $retArr=$this->distributed($dataSign->ToByte());

        $count=0;
        foreach ($retArr as $item){
            $Data=json_decode($item,true);

            if (!isset($Data['uidCount'])){
                print_r("warning skip  getUidCountByGroup()  : ", $item);
                continue;
            }

            $count+=$Data['uidCount'];
        }
        return $count;
    }

    /**
     * @return array
    array(
    '123' => '123',
    '456' => '456'
    )
     */
    public function getAllUidList(): array
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetAllUidList, [
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());

        $client_list=[];
        foreach ($retArr as $item){
            $Data=json_decode($item,true);

            if (!isset($Data['uidList'])){
                print_r("warning skip  getAllUidList()  : ", $item);
                continue;
            }
            foreach ($Data['uidList'] as $client_id){
                $client_list[$client_id]=$client_id;
            }
        }
        return $client_list;

    }

    public function getAllUidCount(): int
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetAllUidCount, [
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());

        $count=0;
        foreach ($retArr as $item){
            $Data=json_decode($item,true);

            if (!isset($Data['uidCount'])){
                print_r("warning skip  getAllUidCount()  : ", $item);
                continue;
            }

            $count+=$Data['uidCount'];
        }
        return $count;
    }

    /**
     * @return array
    array(
    'room-1' => 'room-1',
    'room-2' => 'room-2'
    )
     */
    public function getAllGroupIdList(): array
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetAllGroupIdList, [
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());
        $group_id_list=[];
        foreach ($retArr as $item){
            $Data=json_decode($item,true);

            if (!isset($Data['groupIDList'])){
                print_r("warning skip  getAllGroupIdList()  : ", $item);
                continue;
            }
            foreach ($Data['groupIDList'] as $group_id){
                $group_id_list[$group_id]=$group_id;
            }
        }
        return $group_id_list;
    }

    public function getAllGroupCount(): int
    {
        $dataSign =  Protocol::GenerateSignTimeByte(GatewayCommandGetAllGroupCount, [
        ], $this->signKey);

        $retArr=$this->distributed($dataSign->ToByte());

        $count=0;
        foreach ($retArr as $item){
            $Data=json_decode($item,true);
            if (!isset($Data['groupCount'])){
                print_r("warning skip  getAllGroupCount()  : ", $item);
                continue;
            }
            $count+=$Data['groupCount'];
        }
        return $count;
    }
}