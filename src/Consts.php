<?php


const RegisterForComponent = '/component/ws/register';
const GatewayForSdkPath='/component/http/gateway/sdk';



const ComponentIdentifiersTypeBusiness = 0;
const ComponentIdentifiersTypeGateway = 1;
const CommandComponentHeartbeat = 2;
const CommandComponentAuthRequest = 3;
const CommandComponentGatewayList = 4;
const CommandGatewayForwardUserOnMessage = 5;
const CommandGatewayForwardUserOnClose = 6;
const CommandGatewayForwardUserOnConnect = 7;
const CommandGatewayForwardUserOnError = 8;
const GatewayCommandSendToAll = 9;
const GatewayCommandSendToClient = 10;
const GatewayCommandCloseClient = 11;
const GatewayCommandIsOnline = 12;
const GatewayCommandBindUid = 13;
const GatewayCommandUnbindUid = 14;
const GatewayCommandIsUidOnline = 15;
const GatewayCommandGetClientIdByUid = 16;
const GatewayCommandGetUidByClientId = 17;
const GatewayCommandSendToUid = 18;
const GatewayCommandJoinGroup = 19;
const GatewayCommandLeaveGroup = 20;
const GatewayCommandUngroup = 21;
const GatewayCommandSendToGroup = 22;
const GatewayCommandGetClientIdCountByGroup = 23;
const GatewayCommandGetClientSessionsByGroup = 24;
const GatewayCommandGetAllClientIdCount = 25;
const GatewayCommandGetAllClientSessions = 26;
const GatewayCommandSetSession = 27;
const GatewayCommandUpdateSession = 28;
const GatewayCommandGetSession = 29;
const GatewayCommandGetClientIdListByGroup = 30;
const GatewayCommandGetAllClientIdList = 31;
const GatewayCommandGetUidListByGroup = 32;
const GatewayCommandGetUidCountByGroup = 33;
const GatewayCommandGetAllUidList = 34;
const GatewayCommandGetAllUidCount = 35;
const GatewayCommandGetAllGroupIdList = 36;
const GatewayCommandGetAllGroupCount = 37;