jsonrpcinti
===========

A simple and free implementation (client and server) of the Json-rpc 2.0 specification.

Examples:

Example server:
```php
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
```

Example client:
```php
<pre>
<?php
	include 'lib/jsonrpcinti/class.JsonIntiClient.inc.php';

	$url = 'http://url.to/jsonrpcserver.php';
	$client = new JsonIntiClient($url);

	// Simple request
	$rtn = $client->sendRequest('hello');
	if ($rtn->isError())
		echo 'Request hello error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request hello result: '.$rtn->getResult()."\n";

	// Simple notification
	$client->sendNotification('doSomething');

	// Params request
	$rtn = $client->sendRequest('addOp', array(43, 21));
	if ($rtn->isError())
		echo 'Request addOp error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request addOp result: '.$rtn->getResult()."\n";

	// Unknown method
	$rtn = $client->sendRequest('unknown');
	if ($rtn->isError())
		echo 'Request unknown method error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request unknown method result: '.$rtn->getResult()."\n";

	// Incorrect parameters
	$rtn = $client->sendRequest('addOp');
	if ($rtn->isError())
		echo 'Request addOp error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request addOp result: '.$rtn->getResult()."\n";

	// Incorrect parameters again
	$rtn = $client->sendRequest('hello', 'lalala');
	if ($rtn->isError())
		echo 'Request hello error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request hello result: '.$rtn->getResult()."\n";

	// And again (custom)
	$rtn = $client->sendRequest('addOp', 'lalala');
	if ($rtn->isError())
		echo 'Request addOp error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request addOp result: '.$rtn->getResult()."\n";

	// Retrieving id of request and result
	$rtn = $client->sendRequest('addOp', array(22, 71), $id);
	echo 'Request id: '.$id."\n";
	if ($rtn->isError())
		echo 'Request addOp ('.$rtn->getid().') error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request addOp ('.$rtn->getid().') result: '.$rtn->getResult()."\n";

	echo "BATCH:\n";

	// Doing a batch
	$batch = array();
	$batch[] = $client->constructRequest('hello');
	$batch[] = $client->constructRequest('addOp', array(98, 111));
	$batch[] = $client->constructNotification('doSomething');
	$batch[] = $client->constructRequest('unknown');
	$batch[] = $client->constructNotification('unknown');
	$batch[] = $client->constructRequest('addOp', 'custom error');
	$batch[] = $client->constructRequest('addOp', array(3, 8), $lastid);
	echo 'Last request id: '.$lastid."\n";

	$rtn = $client->sendBatch($batch);

	if ($rtn !== NULL)
	{
		foreach ($rtn as $item)
		{
			if ($item->isError())
				echo 'Request ('.$item->getid().') error: '.$item->getErrorMessage()."\n";
			else
				echo 'Request ('.$item->getid().') result: '.$item->getResult()."\n";
		}
	}
?>
</pre>
```