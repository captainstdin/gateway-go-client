

## 安装
### 方法一 (适用于Composer集成)

```
composer require captainstdin/gateway-go-client"
```

#### 使用时引入 `vendor/autoload.php` 类似如下：

```php
use Captainstdin\GatewayGoClient\Gateway;
require_once '真实路径/vendor/autoload.php'; 
```

### 方法二 （适用于php require单文件调用）

#### 下载源文件到任意目录，手动引入 `src/Gateway.php`, 类似如下：

```php
use Captainstdin\GatewayGoClient\Gateway;
require_once '真实路径/src/Gateway.php';

```

## 使用 SDK(PHP版)


```php
use Captainstdin\GatewayGoClient\Gateway;
// composer安装
require_once '真实路径/vendor/autoload.php';
// 源文件引用
//require_once '真实路径/GatewayClient/Gateway.php';

/**
 * === 指定registerAddress表明与哪个GatewayWorker(集群)通讯。===
 * GatewayWorker里用Register服务来区分集群，即一个GatewayWorker(集群)只有一个Register服务，
 * GatewayClient要与之通讯必须知道这个Register服务地址才能通讯，这个地址格式为 ip:端口 ，
 * 其中ip为Register服务运行的ip(如果GatewayWorker是单机部署则ip就是运行GatewayWorker的服务器ip)，
 * 端口是对应ip的服务器上start_register.php文件中监听的端口，也就是GatewayWorker启动时看到的Register的端口。
 * GatewayClient要想推送数据给客户端，必须知道客户端位于哪个GatewayWorker(集群)，
 * 然后去连这个GatewayWorker(集群)Register服务的 ip:端口，才能与对应GatewayWorker(集群)通讯。
 * 这个 ip:端口 在GatewayClient一侧使用 Gateway::$registerAddress 来指定。
 * 
 * === 如果GatewayClient和GatewayWorker不在同一台服务器需要以下步骤 ===
 * 1、需要设置start_gateway.php中的lanIp为实际的本机内网ip(如不在一个局域网也可以设置成外网ip)，设置完后要重启GatewayWorker
 * 2、GatewayClient这里的Gateway::$registerAddress的地址填写实际运行Register的服务器ip和端口
 * 3、需要开启GatewayWorker所在服务器的防火墙，让以下端口可以被GatewayClient所在服务器访问，
 *    端口包括Rgister服务的端口以及start_gateway.php中lanIp与startPort指定的几个端口
 *
 * === 如果GatewayClient和GatewayWorker在同一台服务器 ===
 * GatewayClient和Register服务都在一台服务器上，ip填写127.0.0.1及即可，无需其它设置。
 **/
$gateway=new GatewayClient(['127.0.0.1:1238'], "da!!bskdhaskld#1238asjiocy89123");

// GatewayClient支持GatewayWorker中的所有接口(Gateway::closeCurrentClient Gateway::sendToCurrentClient除外)
$gateway->sendToAll($data);
$gateway->sendToClient($client_id, $data);
$gateway->closeClient($client_id);
$gateway->isOnline($client_id);
$gateway->bindUid($client_id, $uid);
$gateway->isUidOnline($uid);
$gateway->getClientIdByUid($uid);
$gateway->unbindUid($client_id, $uid);
$gateway->sendToUid($uid, $dat);
$gateway->joinGroup($client_id, $group);
$gateway->sendToGroup($group, $data);
$gateway->leaveGroup($client_id, $group);
$gateway->getClientCountByGroup($group);
$gateway->getClientSessionsByGroup($group);
$gateway->getAllClientCount();
$gateway->getAllClientSessions();
$gateway->setSession($client_id, $session);
$gateway->updateSession($client_id, $session);
$gateway->getSession($client_id);
// .... 不在累赘
```

## 可使用的接口

```php 
/**
 * Author : gpt-3.5-turbo
 */
interface GatewayLibInterface
{

    /**
     * 向所有客户端或者client_id_array指定的客户端发送$send_data数据。如果指定的$client_id_array中的client_id不存在则自动丢弃。
     */
    public function sendToAll(string $data, array $client_id_array, array $exclude_client_id);

    /**
     * 向客户端client_id发送$send_data数据。如果client_id对应的客户端不存在或者不在线则自动丢弃发送数据。
     */
    public function sendToClient(string $client_id, string $send_data);

    /**
     * 断开与client_id对应的客户端的连接。
     */
    public function closeClient(string $client_id);

    /**
     * 判断$client_id是否还在线。
     */
    public function isOnline(string $client_id): int;

    /**
     * 将client_id与uid绑定，以便通过Gateway::sendToUid($uid)发送数据，通过Gateway::isUidOnline($uid)用户是否在线。 uid解释：这里uid泛指用户id或者设备id，用来唯一确定一个客户端用户或者设备。
     */
    public function bindUid(string $client_id, string $uid);

    /**
     * 将client_id与uid解绑。
     */
    public function unbindUid(string $client_id, string $uid);

    /**
     * 判断$uid是否在线，此方法需要配合Gateway::bindUid($client_uid, $uid)使用。
     */
    public function isUidOnline(string $uid): int;

    /**
     * 返回一个数组，数组元素为与uid绑定的所有在线的client_id。如果没有在线的client_id则返回一个空数组。
     */
    public function getClientIdByUid(string $uid): array;

    /**
     * 返回client_id绑定的uid，如果client_id没有绑定uid，则返回null。
     */
    public function getUidByClientId(string $client_id): ?string;

    /**
     * 向uid绑定的所有在线client_id发送数据。 默认uid与client_id是一对多的关系，如果当前uid下绑定了多个client_id，则多个client_id对应的客户端都会收到消息，这类似于PC QQ和手机QQ同时在线接收消息。
     */
    public function sendToUid(string $uid, string $message);

    /**
     * 将client_id加入某个组，以便通过Gateway::sendToGroup发送数据。
     */
    public function joinGroup(string $client_id, string $group);

    /**
     * 将client_id从某个组中删除，不再接收该分组广播(Gateway::sendToGroup)发送的数据。
     */
    public function leaveGroup(string $client_id, string $group);

    /**
     * 取消分组，或者说解散分组。
     */
    public function ungroup(string $group);

    /**
     * 向某个分组的所有在线client_id发送数据。
     */
    public function sendToGroup(string $group, string $message, array $exclude_client_id);

    /**
     * 获取某分组当前在线成连接数（多少client_id在线）。
     */
    public function getClientIdCountByGroup(string $group): int;

    /**
     * 获取某个分组所有在线client_id信息。
     */
    public function getClientSessionsByGroup(string $group): array;

    /**
     * 获取当前在线连接总数（多少client_id在线）。
     */
    public function getAllClientIdCount(): int;

    /**
     * 获取当前所有在线client_id信息。
     */
    public function getAllClientSessions(): array;

    /**
     * 设置某个client_id对应的session。如果对应client_id已经下线或者不存在，则会被忽略。
     */
    public function setSession(string $client_id, array $data);

    /**
     * 更新某个client_id对应的session。如果对应client_id已经下线或者不存在，则会被忽略。
     */
    public function updateSession(string $client_id, array $data);

    /**
     * 获取某个client_id对应的session。
     */
    public function getSession(string $client_id): array;

    /**
     * 获取某个分组所有在线client_id列表。
     */
    public function getClientIdListByGroup(string $group): array;

    /**
     * 获取全局所有在线client_id列表。
     */
    public function getAllClientIdList(): array;

    /**
     * 获取某个分组所有在线uid列表。
     */
    public function getUidListByGroup(string $group): array;

    /**
     * 获取某个分组下的在线uid数量。
     */
    public function getUidCountByGroup(string $group): int;

    /**
     * 获取全局所有在线uid列表。
     */
    public function getAllUidList(): array;

    /**
     * 获取全局所有在线uid数量。
     */
    public function getAllUidCount(): int;

    /**
     * 获取全局所有在线group id列表。
     */
    public function getAllGroupIdList(): array;

    /**
     * 获取全局所有在线group数量。
     */
    public function getAllGroupCount(): int;
}
```