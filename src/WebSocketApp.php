<?php
namespace Smooler;

use Smooler\Caches\Redis as Redisc;

class WebSocketApp
{
	protected $server;
	protected $server_id;
	protected $channel_key;
	protected $redisc;
    const SMOOLER_TIME_TICK_ONLINE = 55000;
    const SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_KEY = 'hash:websocket_server_user_fds:server_id:%u:user_uuid:%s';
    const SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_KEY = 'hash:websocket_server_chatroom_fds:server_id:%u:chatroom_uuid:%u';
    const SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_KEY = 'string:websocket_server_fd_chatroom:server_id:%u:fd:%u';
    const SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_KEY = 'string:websocket_server_fd_user:server_id:%u:fd:%u';
    const SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_EXPIRE = 120;
    const SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_EXPIRE = 120;
    const SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_EXPIRE = 120;
    const SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_EXPIRE = 120;

	function __construct() 
	{
		$this->context = new Context();
		$this->environment = new Environment();
		$this->config = new Config();
		$this->lang = new Lang();
		$this->log = new Log();
		$this->exception = new Exception();
		$this->mysql = new Mysql();
		$this->redis = new Redis();
        $serverId = $this->config->get('app.sid');
        if (!$serverId) {
        	throw new \Exception("Error server Id");
        }
		$this->server_id = $serverId;
        $channelKey = $this->config->get('app.channel_key');
        if (!$channelKey) {
        	throw new \Exception("Error channel key");
        }
		$this->channel_key = $channelKey;
		if (!defined('RESPONSE_TYPE_OPEN')) {
        	throw new \Exception("Error RESPONSE_TYPE_OPEN");
		}
		if (!defined('RESPONSE_TYPE_HEART_BEAT')) {
        	throw new \Exception("Error RESPONSE_TYPE_HEART_BEAT");
		}
		if (!defined('RESPONSE_TYPE_JOIN_CHATROOM')) {
        	throw new \Exception("Error RESPONSE_TYPE_JOIN_CHATROOM");
		}
		if (!defined('RESPONSE_TYPE_DEPART_CHATROOM')) {
        	throw new \Exception("Error RESPONSE_TYPE_DEPART_CHATROOM");
		}
		if (!defined('RESPONSE_TYPE_SYSTEM_MESSAGE')) {
        	throw new \Exception("Error RESPONSE_TYPE_SYSTEM_MESSAGE");
		}
		if (!defined('RESPONSE_TYPE_USER_MESSAGE')) {
        	throw new \Exception("Error RESPONSE_TYPE_USER_MESSAGE");
		}
		if (!defined('RESPONSE_TYPE_USER_PUSH')) {
        	throw new \Exception("Error RESPONSE_TYPE_USER_PUSH");
		}
		if (!defined('RESPONSE_TYPE_CHATROOM_MESSAGE')) {
        	throw new \Exception("Error RESPONSE_TYPE_CHATROOM_MESSAGE");
		}
		if (!defined('RESPONSE_TYPE_CHATROOM_PUSH')) {
        	throw new \Exception("Error RESPONSE_TYPE_CHATROOM_PUSH");
		}
		if (!defined('RESPONSE_TYPE_ERROR')) {
        	throw new \Exception("Error RESPONSE_TYPE_ERROR");
		}
		if (!defined('REQUEST_TYPE_HEART_BEAT')) {
        	throw new \Exception("Error REQUEST_TYPE_HEART_BEAT");
		}
		if (!defined('REQUEST_TYPE_JOIN_CHATROOM')) {
        	throw new \Exception("Error REQUEST_TYPE_JOIN_CHATROOM");
		}
		if (!defined('REQUEST_TYPE_DEPART_CHATROOM')) {
        	throw new \Exception("Error REQUEST_TYPE_DEPART_CHATROOM");
		}
		$this->redisc = new Redisc;
	}

	function registerServer($server) 
	{
		$this->server = $server;
	}

	function handleWorkStart($worker_id) 
	{
		swoole_time_tick(
			1000 * 3600,
			function(){
				$this->singleton->clearCache();
			}
		);
		if (0 == $worker_id) {
	        swoole_time_tick(
	        	$this::SMOOLER_TIME_TICK_ONLINE, 
	        	function () {
		            $time = time();
		            $this->handleOnline($time);
		        }
		    );

	        go(function() {
	            if ($this->redisc->subscribe($this->channel_key)) // 或者使用psubscribe
	            {
	                while ($msg = $this->redisc->recv()) {
	                    // msg是一个数组, 包含以下信息
	                    // $type # 返回值的类型：显示订阅成功
	                    // $name # 订阅的频道名字 或 来源频道名字
	                    // $info # 目前已订阅的频道数量 或 信息内容
	                    list($type, $name, $info) = $msg;
	                    if ($type == 'subscribe') // 或psubscribe
	                    {
	                        // 频道订阅成功消息，订阅几个频道就有几条
	                    }
	                    else if ($type == 'unsubscribe' && $info == 0) // 或punsubscribe
	                    {
	                        break; // 收到取消订阅消息，并且剩余订阅的频道数为0，不再接收，结束循环
	                    }
	                    else if ($type == 'message') // 若为psubscribe，此处为pmessage
	                    {
	                        $info = json_decode($info, true);
	                        if (isset($info['response_type']) && isset($info['data'])) {
	                            switch ($info['response_type']) {
	                                case 
	                                	RESPONSE_TYPE_SYSTEM_MESSAGE,
	                                	RESPONSE_TYPE_USER_MESSAGE,
	                                	RESPONSE_TYPE_USER_PUSH,
	                                	: 
	                                    if (isset($info['data']['to_user_uuid'])) {
	                                        $userFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_KEY;
	                                        $userFdsKey = sprintf($userFdsKey, $this->server_id, $info['data']['to_user_uuid']);
	                                        $fds = $this->redisc->hgetall($userFdsKey);
	                                        if ($fds) {
	                                            foreach ($fds as $key => $value) {
	                                                if (0 == $key%2) {
	                                                    if ($this->server->exist($value)) {
	                                                        $res = [
	                                                            'response_id' => 0,
	                                                            'response_type' => $info['response_type'],
	                                                            'error' => null,
	                                                            'data' => $info['data'],
	                                                        ];
	                                                        $this->server->push($value, json_encode($res));
	                                                    } else {
	                                                        $this->redisc->hdel($userFdsKey, $value);
	                                                    }
	                                                }
	                                            }
	                                        }
	                                    }
	                                    break;
	                                case 
	                                	RESPONSE_TYPE_CHATROOM_MESSAGE,
	                                	RESPONSE_TYPE_CHATROOM_PUSH,
	                                	:
	                                    if (isset($info['data']['chatroom_uuid'])) {
	                                        $chatroomFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_KEY;
	                                        $chatroomFdsKey = sprintf($chatroomFdsKey, $this->server_id, $info['data']['chatroom_uuid']);
	                                        $fds = $this->redisc->hgetall($chatroomFdsKey);
	                                        if ($fds) {
	                                            foreach ($fds as $key => $value) {
	                                                if (0 == $key%2) {
	                                                    if ($this->server->exist($value)) {
	                                                        $res = [
	                                                            'response_id' => 0,
	                                                            'response_type' => $info['response_type'],
	                                                            'error' => null,
	                                                            'data' => $info['data'],
	                                                        ];
	                                                        $this->server->push($value, json_encode($res));
	                                                    } else {
	                                                        $this->redisc->hdel($chatroomFdsKey, $value);
	                                                    }
	                                                }
	                                            }
	                                        }
	                                    }
	                                    break;
	                            }
	                        }
	                    }
	                }
	            }
	        });
		}
    }

	function handleShutdown($fd = 0, $responseId = 0) 
	{
        $error = error_get_last();
        var_dump($error);
        switch ($error['type'] ?? null) {
            case E_ERROR :
            case E_PARSE :
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
	            if ($fd) {
			        $this->server->push($fd, json_encode([
			            'response_id' => $responseId,
			            'response_type' => RESPONSE_TYPE_ERROR,
			            'error' => [
			                'code' => 0,
			                'message' => $error['message']
			            ],
			            'data' => $res['data'] ?? null,
			        ]));
	            }
                $app->log->error($error['message'] . ' ' . $error['file'] . ' ' . $error['line']);
                break;
        }
    }

    function handleOnline($time) 
    {
        $chatroomUuids = $this->getChatroomUuids(); // 获取聊天室
        $chatroomFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_KEY;
        $fdChatroomKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_KEY;
        $fdUserKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_KEY;
        foreach ($chatroomUuids as $chatroomUuid) {
            $chatroomFdsTempKey = sprintf($chatroomFdsKey, $serverId, $chatroomUuid);
            $fds = $this->redisc->hgetall($chatroomFdsTempKey);
            foreach ($fds as $k => $fd) {
                if (0 == $k%2) {
                    if ($this->server->exist($fd)) {
                        $fdChatroomTempKey = sprintf($fdChatroomKey, $serverId, $fd);
                        $chatroomUuid = $this->redisc->get($fdChatroomTempKey);
                        if ($chatroomUuid != $chatroomUuid) {
                            $this->redisc->hdel($chatroomFdsTempKey, $fd);
                        } else {
		                    $fdUserTempKey = sprintf($fdUserKey, $serverId, $fd);
		                    $userUuid = $this->redisc->get($fdUserTempKey);
		                    if ($userUuid) {
		                    	$this->handleUserView($chatroomUuid, $time, $userUuid); // 上报用户观看记录
		                    }
                        }
                    } else {
                        $this->redisc->hdel($chatroomFdsTempKey, $fd);
                    }
                }
            }
            $count = $this->redisc->hlen($chatroomFdsTempKey);
            if ($count) {
            	$this->handleChatroomServerView($chatroomUuid, $time, $serverId, $count); // 统计直播间观看人数
            }
        }
	}

	function handleOpen($request) 
	{
		try {
		    $chatToken = $request->get['chat_token'] ?? null;
		    if ($chatToken) {
		        $userUuid = $this->getUserUuidByChatToken($chatToken);
		    	if (isset($userUuid['error'])) {
        			$this->server->close($request->fd);
        			return;
		    	}
		        $this->server->push($request->fd, json_encode([
		            'response_id' => 0,
		            'response_type' => RESPONSE_TYPE_OPEN,
					'error' => null,
		            'data' => null,
		        ]));

		        if ($userUuid) {
		            $userFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_KEY;
		            $userFdsKey = sprintf($userFdsKey, $this->server_id, $userUuid);
		            $this->redisc->hset($userFdsKey, $request->fd, 1);
		            $userFdsExpire = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_EXPIRE;
		            $this->redisc->expire($userFdsKey, $userFdsExpire);

		            $fdUserKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_KEY;
		            $fdUserKey = sprintf($fdUserKey, $this->server_id, $request->fd);
		            $this->redisc->set($fdUserKey, $userUuid);
		            $fdUserExpire = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_EXPIRE;
		            $this->redisc->expire($fdUserKey, $fdUserExpire);

		            $userMessageList = $this->getUnreadUserMessageList($userUuid);;
		            if (isset($userMessageList['error'])) {
						$this->responseError($request->fd, 0, $userMessageList);
		            } else {
		                foreach ($userMessageList as $key => $value) {
		                    $this->server->push($request->fd, json_encode([
		                        'response_id' => 0,
		                        'response_type' => RESPONSE_TYPE_USER_MESSAGE,
								'error' => null,
		                        'data' => [
		                            'to_user_uuid' => $userUuid,
		                            'result' => [
		                                'message_info' => $value,
		                            ]
		                        ],
		                    ]));
		                }
		            }

		            $systemMessageList = $this->getUnreadSystemMessageList($userUuid);;
		            if (isset($systemMessageList['error'])) {
						$this->responseError($request->fd, 0, $systemMessageList);
		            } else {
		                foreach ($systemMessageList as $key => $value) {
		                    $this->server->push($request->fd, json_encode([
		                        'response_id' => 0,
		                        'response_type' => RESPONSE_TYPE_SYSTEM_MESSAGE,
								'error' => null,
		                        'data' => [
		                            'to_user_uuid' => $userUuid,
		                            'result' => [
		                                'message_info' => $value,
		                            ]
		                        ],
		                    ]));
		                }
		            }
		        }
		    } else {
        		$this->server->close($request->fd);
		    }
		} catch (\Throwable $e) {
			if ($e instanceof \Swoole\ExitException) {
				return;
			}
			$res = $this->exception->handle($e);
			$this->responseError($request->fd, 0, $res);
		} 
	}

	function handleMessage($frame) 
	{
		try {
		    $data = json_decode($frame->data, true);
		    if (isset($data['request_type']) && isset($data['requset_id'])) {
		        switch ($data['request_type']) {
		            case REQUEST_TYPE_HEART_BEAT: // 心跳
		                $fdUserKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_KEY;
		                $fdUserKey = sprintf($fdUserKey, $this->server_id, $frame->fd);
		                $exsit = $this->redisc->exists($fdUserKey);
		                if ($exsit) {
		                    $fdUserExpire = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_EXPIRE;
		                    $this->redisc->expire($fdUserKey, $fdUserExpire);

		                    $userUuid = $this->redisc->get($fdUserKey);
				            $userFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_KEY;
				            $userFdsKey = sprintf($userFdsKey, $this->server_id, $userUuid);
				            $userFdsExpire = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_USER_FDS_EXPIRE;
				            $this->redisc->expire($userFdsKey, $userFdsExpire);
		                }
		                
		                $fdChatroomKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_KEY;
		                $fdChatroomKey = sprintf($fdChatroomKey, $this->server_id, $frame->fd);
		                $exsit = $this->redisc->exists($fdChatroomKey);
		                if ($exsit) {
		                    $fdChatroomExpire = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_EXPIRE;
		                    $this->redisc->expire($fdChatroomKey, $fdChatroomExpire);

		                    $chatroomUuid = $redis->get($fdChatroomKey);
				            $chatroomFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_KEY;
				            $chatroomFdsKey = sprintf($chatroomFdsKey, $this->server_id, $chatroomUuid);
		                    $chatroomFdsExpire = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_EXPIRE;
		                    $this->redisc->expire($chatroomFdsKey, $chatroomFdsExpire);
		                }

		                $this->server->push($frame->fd, json_encode([
		                    'response_id' => $data['requset_id'],
		                    'response_type' => RESPONSE_TYPE_HEART_BEAT,
							'error' => null,
		                    'data' => null,
		                ]));
		                break;
		            case REQUEST_TYPE_JOIN_CHATROOM: // 进入聊天室
		                if (isset($data['data']) && isset($data['data']['chatroom_uuid'])) {
		                    $chatroomFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_KEY;
		                    $chatroomFdsKey = sprintf($chatroomFdsKey, $this->server_id, $data['data']['chatroom_uuid']);
		                    $this->redisc->hset($chatroomFdsKey, $frame->fd, 1);
		                    $chatroomFdsExpire = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_EXPIRE;
		                    $this->redisc->expire($chatroomFdsKey, $chatroomFdsExpire);

		                    $fdChatroomKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_KEY;
		                    $fdChatroomKey = sprintf($fdChatroomKey, $this->server_id, $frame->fd);
		                    $this->redisc->set($fdChatroomKey, $data['data']['chatroom_uuid']);
		                    $fdChatroomExpire = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_EXPIRE;
		                    $this->redisc->expire($fdChatroomKey, $fdChatroomExpire);

		                    $this->server->push($frame->fd, json_encode([
		                        'response_id' => $data['requset_id'],
		                    	'response_type' => RESPONSE_TYPE_JOIN_CHATROOM ,
								'error' => null,
		                        'data' => [
		                            'chatroom_uuid' => $data['data']['chatroom_uuid']
		                        ],
		                    ]));
		                }
		                break;
		            case REQUEST_TYPE_DEPART_CHATROOM: // 离开聊天室
		                if (isset($data['data']) && isset($data['data']['chatroom_uuid'])) {
		                    $chatroomFdsKey = $this::SMOOLER_REDIS_HASH_WEBSOCKET_SERVER_CHATROOM_FDS_KEY;
		                    $chatroomFdsKey = sprintf($chatroomFdsKey, $this->server_id, $data['data']['chatroom_uuid']);
		                    $this->redisc->hdel($chatroomFdsKey, $frame->fd);

		                    $fdChatroomKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_KEY;
		                    $fdChatroomKey = sprintf($fdChatroomKey, $this->server_id, $frame->fd);
		                    $this->redisc->del($fdChatroomKey);
		                    $this->server->push($frame->fd, json_encode([
		                        'response_id' => $data['requset_id'],
		                    	'response_type' => RESPONSE_TYPE_DEPART_CHATROOM,
								'error' => null,
		                        'data' => [
		                            'chatroom_uuid' => $data['data']['chatroom_uuid']
		                        ],
		                    ]));
		                }
		                break;
		        }
		    } else {
		        $this->server->close($frame->fd);
		    }
		} catch (\Throwable $e) {
			if ($e instanceof \Swoole\ExitException) {
				return;
			}
			$res = $this->exception->handle($e);
			$this->responseError($frame->fd, $data['requset_id'], $res);
		} 
	}

	function handleClose($fd) 
	{
		try {
		    $fdUserKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_USER_KEY;
		    $fdUserKey = sprintf($fdUserKey, $this->server_id, $fd);
		    $fdChatroomKey = $this::SMOOLER_REDIS_STRING_WEBSOCKET_SERVER_FD_CHATROOM_KEY;
		    $fdChatroomKey = sprintf($fdChatroomKey, $this->server_id, $fd);
		    $this->redisc->delete($fdUserKey);
		    $this->redisc->delete($fdChatroomKey);
		} catch (\Throwable $e) {
			if ($e instanceof \Swoole\ExitException) {
				return;
			}
			$res = $this->exception->handle($e);
		} 
	}

	function responseError($fd, $responseId, $res) 
	{
		$message = '';
		if (isset($res['message']) && $res['message']) {
			$message = $res['message'];
		} else {
			$message = $this->lang->get('error.' . $res['error']);
			if ($message) {
				if (isset($res['params']) && $res['params']) {
					$message = vsprintf($message, $res['params']);
				}
			} else {
				$message = 'unknown';
			}
		}
		
        $this->server->push($fd, json_encode([
            'response_id' => $responseId,
            'response_type' => RESPONSE_TYPE_ERROR,
            'error' => [
                'code' => $res['error'],
                'message' => $message
            ],
            'data' => $res['data'] ?? null,
        ]));
	}
}
