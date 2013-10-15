<?php
namespace Swoole\Network;

class BlockTCP extends \Swoole\Server
{
	public $server_sock;
	public $server_socket_id;
	public $client_sock;
	public $buffer_size = 8192;
	public $timeout_micro = 1000;

	function __construct($host, $port, $timeout=30)
	{
		parent::__construct($host,$port,$timeout);
	}
	/**
	 * 向client发送数据
	 * @param $client_id
	 * @param $data
	 * @return unknown_type
	 */
	function send($client_id,$data)
	{
		$this->sendData($this->client_sock[$client_id], $data);
	}

	function shutdown()
	{
		//关闭所有客户端
		foreach($this->client_sock as $k=>$sock)
		{
			sw_socket_close($sock, $this->client_event[$k]);
		}
		//关闭服务器端
		sw_socket_close($this->server_sock, $this->server_event);
		$this->protocol->onShutdown($this);
	}

	function close($client_id)
	{
		sw_socket_close($this->client_sock[$client_id]);
		$this->client_sock[$client_id] = null;
		unset($this->client_sock[$client_id]);
		$this->protocol->onClose($this, $client_id, 0);
	}

	function server_loop()
	{
		while($this->client_sock[0] = stream_socket_accept($this->server_sock, -1))
		{
			stream_set_blocking($this->client_sock[0], 1);
			stream_set_timeout($this->client_sock[0], 0, $this->timeout_micro);
			if(feof($this->client_sock[0])) $this->close(0);

			//堵塞Server必须读完全部数据
            $data = sw_fread_stream($this->client_sock[0],$this->buffer_size);
			$this->protocol->onReceive($this, 0, 0, $data);
		}
	}

	function run($setting)
	{
		//建立服务器端Socket
		$this->server_sock = $this->create("tcp://{$this->host}:{$this->port}");
		stream_set_timeout($this->server_sock, $this->timeout);
		$this->server_socket_id = (int)$this->server_sock;
		stream_set_blocking($this->server_sock, 1);

        $this->spawn($setting);
		$this->protocol->onStart($this);
		$this->server_loop();
	}
}
