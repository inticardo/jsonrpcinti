<?php
/*
	JsonIntiServer and auxiliar Exception classes
	jsonrpcinti project - https://github.com/inticardo/jsonrpcinti

	(c) 2013 - Juan Máiquez Corbalán (Inti / Int_10h) - contacto@int10h.es
	Licensed under Apache License, Version 2.0, January 2004
	http://www.apache.org/licenses/
	
	Last changes: 2013-10-23

	This is a simple and free implementation of the JSON-RPC 2.0 specification.
	To see some examples please refer to README.md or ./examples/ directory.

	Dependences:

	- PHP 5.

	Notes and decisions:

	- JsonIntiServer is able to provide only two types of functions: WITH ONLY 
	ONE parameter and WITHOUT parameters. If you need more than one parameter 
	in your remote procedures, you must pass an array or hash.
	- JsonIntiServer only works passing data with RAW POST method. GET method 
	is unsupported.
	- JsonIntiServer don't have any mapping with conventional HTTP errors 
	(like 404, 500, etc).
	- The chosen content-type used for passing json messages is 
	application/json.

	See also:

	- http://www.jsonrpc.org/spec.html 
	- http://en.wikipedia.org/wiki/JSON-RPC
	- http://www.jsonrpc.org/historical/json-rpc-over-http.html
	- https://groups.google.com/forum/#!searchin/json-rpc/http/json-rpc/VNIH0WaxH5U/E55v-T5iy-YJ

*/

class JsonIntiServer
{
	const VERSION = '2.0';

	const JSI_PARSE_E = -32700;
	const JSI_INVALID_REQUEST_E = -32600;
	const JSI_METHOD_NOT_FOUND_E = -32601;
	const JSI_INVALID_PARAMS_E = -32602;
	const JSI_INTERNAL_E = -32603;
	const JSI_IMPLEMENTATION_E = -32001;

	const CONTENT_TYPE = 'application/json';

	protected $funcs;

	public function __construct($funcs = array())
	{
		$this->funcs = array();

		if (is_array($funcs))
			foreach ($funcs as $func)
				$this->provide($func);
	}

	public function provide($func)
	{
		$f = new ReflectionFunction($func);
		if (count($f->getParameters()) > 1) return;
		$this->funcs[$func] = count($f->getParameters()) == 0? FALSE: TRUE;
	}

	protected function call($method, $params = NULL)
	{
		if ($params !== NULL)
			$rtn = $method($params);
		else
			$rtn = $method();

		return $rtn;
	}

	protected function createError($message = 'Internal server error', $code = self::JSI_INTERNAL_E, $id = NULL, $data = NULL, $jsonencode = TRUE)
	{
		if (!is_string($message)) return FALSE;
		if ($id !== NULL && !is_integer($id) && !is_string($id)) return FALSE;
		if ($data !== NULL && !is_array($data)) return FALSE;
		if (!is_integer($code)) return FALSE;

		$error = array(
			'code' => $code,
			'message' => $message
		);

		if ($data !== NULL) $error['data'] = $data;

		if ($jsonencode)
			return json_encode(array(
				'jsonrpc' => self::VERSION,
				'error' => $error,
				'id' => $id
			));
		else
			return array(
				'jsonrpc' => self::VERSION,
				'error' => $error,
				'id' => $id
			);
	}

	protected function createResponse($result, $id, $jsonencode = TRUE)
	{
		if (!is_integer($id) && !is_string($id)) return FALSE;

		if ($jsonencode)
			return json_encode(array(
				'jsonrpc' => self::VERSION,
				'result' => $result,
				'id' => $id
			));
		else
			return array(
				'jsonrpc' => self::VERSION,
				'result' => $result,
				'id' => $id
			);
	}

	protected function createBatchResponse($responses, $jsondecodeitems = TRUE)
	{
		if (!is_array($responses)) return FALSE;
		if (count($responses) == 0) return FALSE;

		$rtn = array();
		foreach ($responses as $item)
			$rtn[] = $jsondecodeitems? json_decode($item, TRUE): $item;

		return json_encode($rtn);
	}

	protected function sendResponse($response)
	{
		header('Content-type: ' . self::CONTENT_TYPE);
		echo $response;
		exit;
	}

	protected function getRequest()
	{
		$pd = file_get_contents("php://input");
		if (isset($pd)) return $pd;
		else return '';
	}

	public function handler()
	{
		$jsonrequest = $this->getRequest();

		$result = $this->analyzeRequest($jsonrequest);
		if ($result['error'] !== NULL)
		{
			$error = $this->createError('Internal server error', self::JSI_INTERNAL_E);
			switch ($result['error']) 
			{
				case self::JSI_INVALID_REQUEST_E:
					$error = $this->createError('Invalid request', self::JSI_INVALID_REQUEST_E);
					break;
				case self::JSI_PARSE_E:
					$error = $this->createError('Parse error', self::JSI_PARSE_E);
					break;
			}
			$this->sendResponse($error);
		}

		if ($result['single'])
		{
			$id = $result['request']['a_id']? $result['request']['id']: NULL;

			if (isset($result['request']['error']))
			{
				$error = $this->createError('Internal server error', self::JSI_INTERNAL_E);
				switch ($result['request']['error']) 
				{
					case self::JSI_INVALID_REQUEST_E:
						$error = $this->createError('Invalid request', self::JSI_INVALID_REQUEST_E, $id);
						break;
					case self::JSI_METHOD_NOT_FOUND_E:
						$error = $this->createError('Method not found', self::JSI_METHOD_NOT_FOUND_E, $id);
						break;
					case self::JSI_INVALID_PARAMS_E:
						$error = $this->createError('Invalid parameters', self::JSI_INVALID_PARAMS_E, $id);
						break;
				}
				$this->sendResponse($error);
			}

			$params = $result['request']['a_params']? $result['request']['params']: NULL;
			try
			{
				$ret = $this->call($result['request']['method'], $params);
			}
			catch (JsonIntiInvalidParamsException $ex)
			{
				$error = $this->createError($ex->getMessage(), self::JSI_INVALID_PARAMS_E, $id);
				$this->sendResponse($error);
			}
			catch (JsonIntiImplementationException $ei)
			{
				$error = $this->createError($ei->getMessage(), self::JSI_IMPLEMENTATION_E, $id);
				$this->sendResponse($error);
			}

			if ($id !== NULL)
			{
				$response = $this->createResponse($ret, $id);
				$this->sendResponse($response);
			}
		}
		else
		{		
			$responses = array();
			foreach ($result['request'] as $resul)
			{
				$id = $resul['a_id']? $resul['id']: NULL;

				if (isset($resul['error']))
				{
					$error = $this->createError('Internal server error', self::JSI_INTERNAL_E);
					switch ($resul['error']) 
					{
						case self::JSI_INVALID_REQUEST_E:
							$error = $this->createError('Invalid request', self::JSI_INVALID_REQUEST_E, $id, NULL, FALSE);
							break;
						case self::JSI_METHOD_NOT_FOUND_E:
							$error = $this->createError('Method not found', self::JSI_METHOD_NOT_FOUND_E, $id, NULL, FALSE);
							break;
						case self::JSI_INVALID_PARAMS_E:
							$error = $this->createError('Invalid parameters', self::JSI_INVALID_PARAMS_E, $id, NULL, FALSE);
							break;
					}
					$responses[] = $error;
					continue;
				}


				$params = $resul['a_params']? $resul['params']: NULL;
				try
				{
					$ret = $this->call($resul['method'], $params);
				}
				catch (JsonIntiInvalidParamsException $ex)
				{
					$error = $this->createError($ex->getMessage(), self::JSI_INVALID_PARAMS_E, $id, NULL, FALSE);
					$responses[] = $error;
					continue;
				}
				catch (JsonIntiImplementationException $ei)
				{
					$error = $this->createError($ei->getMessage(), self::JSI_IMPLEMENTATION_E, $id, NULL, FALSE);
					$responses[] = $error;
					continue;
				}

				if ($id !== NULL)
				{
					$response = $this->createResponse($ret, $id, FALSE);
					$responses[] = $response;
				}
			}
			if (count($responses) > 0)
			{
				$r = $this->createBatchResponse($responses, FALSE);
				$this->sendResponse($r);
			}
		}
	}

	protected function analyzeRequest($jsonrequest)
	{
		$rtn = array(
			'jsonrequest' => $jsonrequest,
			'request' => NULL,
			'error' => NULL
		);

		$jsonrequest = trim($jsonrequest);

		if (strlen($jsonrequest) == 0)
		{
			$rtn['error'] = self::JSI_INVALID_REQUEST_E;
			return $rtn;
		}

		if ($jsonrequest[0] == '[')
			$rtn['single'] = FALSE;
		elseif ($jsonrequest[0] == '{')
			$rtn['single'] = TRUE;
		else
		{
			$rtn['error'] = self::JSI_INVALID_REQUEST_E;
			return $rtn;
		}

		$request = json_decode($jsonrequest, TRUE);

		if ($request === NULL)
		{
			$rtn['error'] = self::JSI_PARSE_E;
			return $rtn;
		}

		$rtn['request'] = $request;

		if ($rtn['single'])
		{
			$rtn['request']['a_id'] = isset($request['id'])? TRUE: FALSE;

			if (!isset($request['jsonrpc']) || $request['jsonrpc'] != self::VERSION)
			{
				$rtn['request']['error'] = self::JSI_INVALID_REQUEST_E;
				return $rtn;
			}
			if (!isset($request['method']) || !is_string($request['method']))
			{
				$rtn['request']['error'] = self::JSI_INVALID_REQUEST_E;
				return $rtn;
			}
			if (!in_array($request['method'], array_keys($this->funcs)))
			{
				$rtn['request']['error'] = self::JSI_METHOD_NOT_FOUND_E;
				return $rtn;
			}

			$rtn['request']['a_params'] = isset($request['params'])? TRUE: FALSE;
			if ($rtn['request']['a_params'] != $this->funcs[$request['method']] )
			{
				$rtn['request']['error'] = self::JSI_INVALID_PARAMS_E;
				return $rtn;
			}
		}
		else
		{
			foreach ($request as $n => $one)
			{
				$rtn['request'][$n]['a_id'] = isset($one['id'])? TRUE: FALSE;

				if (!isset($one['jsonrpc']) || $one['jsonrpc'] != self::VERSION)
				{
					$rtn['request'][$n]['error'] = self::JSI_INVALID_REQUEST_E;
					continue;
				}
				if (!isset($one['method']) || !is_string($one['method']))
				{
					$rtn['request'][$n]['error'] = self::JSI_INVALID_REQUEST_E;
					continue;
				}
				if (!in_array($one['method'], array_keys($this->funcs)))
				{
					$rtn['request'][$n]['error'] = self::JSI_METHOD_NOT_FOUND_E;
					continue;
				}

				$rtn['request'][$n]['a_params'] = isset($one['params'])? TRUE: FALSE;
				if ($rtn['request'][$n]['a_params'] != $this->funcs[$rtn['request'][$n]['method']] )
				{
					$rtn['request'][$n]['error'] = self::JSI_INVALID_PARAMS_E;
					continue;
				}
			}
		}

		return $rtn;
	}
}

class JsonIntiInvalidParamsException extends Exception 
{
	public function __construct($message = 'Invalid parameters')
	{
		parent::__construct($message, JsonIntiServer::JSI_INVALID_PARAMS_E, NULL);
	}
}

class JsonIntiImplementationException extends Exception
{
	public function __construct($message = 'Implementation error')
	{
		parent::__construct($message, JsonIntiServer::JSI_IMPLEMENTATION_E, NULL);
	}
}

?>