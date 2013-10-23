<?php
	include 'lib/jsonrpcinti/class.JsonIntiServer.inc.php';

	function hello()
	{
		return 'Hello world!';
	}

	function addOp($params)
	{
		if (!is_array($params))
			throw new JsonIntiInvalidParamsException('Invalid parameters custom error');

		list($a, $b) = $params;

		return $a + $b;
	}

	function doSomething()
	{
		// something
	}

	$server = new JsonIntiServer(array(
		'hello',
		'addOp',
		'doSomething'
	));

	/* This is valid too: */
	// $server->provide('hello');
	// $server->provide('addOp');
	// $server->provide('doSomething');

	$server->handler();
?>