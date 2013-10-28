<?php
/*
	JsonIntiClient and result management classes
	jsonrpcinti project - https://github.com/inticardo/jsonrpcinti

	(c) 2013 - Juan Máiquez Corbalán (Inti / Int_10h) - contacto@int10h.es
	Licensed under Apache License, Version 2.0, January 2004
	http://www.apache.org/licenses/
	
	Last changes: 2013-10-28

	This is a simple and free implementation of the JSON-RPC 2.0 specification.
	To see some examples please refer to README.md or ./examples/ directory.

	Dependences:

	- PHP 5.
	- PHP cURL.

	See also:

	- http://www.jsonrpc.org/spec.html 
	- http://en.wikipedia.org/wiki/JSON-RPC
	- http://www.jsonrpc.org/historical/json-rpc-over-http.html
	- https://groups.google.com/forum/#!searchin/json-rpc/http/json-rpc/VNIH0WaxH5U/E55v-T5iy-YJ

*/

class JsonIntiClient
{
	const VERSION = '2.0';

	const JSI_PARSE_E = -32700;
	const JSI_INVALID_REQUEST_E = -32600;
	const JSI_METHOD_NOT_FOUND_E = -32601;
	const JSI_INVALID_PARAMS_E = -32602;
	const JSI_INTERNAL_E = -32603;
	const JSI_IMPLEMENTATION_E = -32001;

	const CONTENT_TYPE = 'application/json';

	protected $rpcserver;
	protected $enum;

	public function __construct($rpcserver)
	{
		$this->rpcserver = $rpcserver;
		$this->enum = rand(0, 998);
	}

	public function sendRequest($method, $params = NULL, &$id = -1)
	{
		if (!is_string($method)) return NULL;

		$this->enum++;
		$id = $this->enum;

		$request = array(
			'jsonrpc' => self::VERSION,
			'method' => $method,
			'id' => $this->enum
		);

		if ($params !== NULL) $request['params'] = $params;

		$post = json_encode($request);
		$result = $this->sendAndReceive($post);

		$decoded = json_decode($result, TRUE);

		return new JsonIntiResult($decoded, $decoded === NULL);
	}

	public function sendNotification($method, $params = NULL)
	{
		if (!is_string($method)) return;

		$request = array(
			'jsonrpc' => self::VERSION,
			'method' => $method
		);

		if ($params !== NULL) $request['params'] = $params;

		$post = json_encode($request);

		$this->sendAndReceive($post);
	}

	public function constructRequest($method, $params = NULL, &$id = -1)
	{
		if (!is_string($method)) return NULL;

		$this->enum++;
		$id = $this->enum;

		$request = array(
			'jsonrpc' => self::VERSION,
			'method' => $method,
			'id' => $this->enum
		);

		if ($params !== NULL) $request['params'] = $params;

		return $request;
	}

	public function constructNotification($method, $params = NULL)
	{
		if (!is_string($method)) return NULL;

		$request = array(
			'jsonrpc' => self::VERSION,
			'method' => $method
		);

		if ($params !== NULL) $request['params'] = $params;

		return $request;
	}

	public function sendBatch($array)
	{
		if (!is_array($array)) return array();

		$post = json_encode($array);
		$result = $this->sendAndReceive($post);
		$decoded = json_decode($result, TRUE);

		if ($decoded === NULL) return array();

		$rtn = array();
		foreach ($decoded as $item)
			$rtn[] = new JsonIntiResult($item);
		
		return $rtn;
	}

	protected function sendAndReceive($post)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->rpcserver);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: '. self::CONTENT_TYPE,
			'Content-Length: ' . strlen($post))
		);

		$result = curl_exec($curl);
		if (curl_errno($curl)) return FALSE;
		curl_close($curl);

		return $result;
	}
}

class JsonIntiResult
{
	protected $data;
	protected $iserror;

	public function __construct($data, $iserror = FALSE)
	{
		$this->data = $data;
		$this->iserror = $iserror;

		if (!$iserror && isset($data['error']) && !isset($data['result']))
			$this->iserror = TRUE;
	}

	public function isError()
	{
		return $this->iserror;
	}

	public function getErrorMessage()
	{
		if (!$this->isError() || !isset($this->data['error'])) return NULL;
		return $this->data['error']['message'];
	}

	public function getErrorCode()
	{
		if (!$this->isError() || !isset($this->data['error'])) return NULL;
		return $this->data['error']['code'];
	}

	public function getResult()
	{
		if ($this->isError()) return NULL;
		return $this->data['result'];
	}

	public function getId()
	{
		if (isset($this->data['id'])) return $this->data['id'];
		return NULL;
	}
}

?>