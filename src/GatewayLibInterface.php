<?php

namespace Captainstdin\GatewayGoClient;

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