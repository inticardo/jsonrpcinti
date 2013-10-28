jsonrpcinti
===========

A simple and free implementation (client and server) of the [JSON-RPC 2.0 specification](http://www.jsonrpc.org/specification).

Dependences
-----------

* [PHP 5](http://php.net/downloads.php).
* [PHP cURL](http://php.net/manual/en/book.curl.php) (client).
* [jQuery](http://jquery.com/) (javascript client)

Notes and decisions
-------------------

* JsonIntiServer is able to provide only two types of functions: WITH ONLY ONE parameter and WITHOUT parameters. If you need more than one parameter in your remote procedures, you must pass an array or hash.
* JsonIntiServer only works passing data with RAW POST method. GET method is unsupported.
* JsonIntiServer don't have any mapping with conventional HTTP errors (like 404, 500, etc).
* The chosen content-type used for passing json messages is *application/json*.

See also
--------

* http://www.jsonrpc.org/spec.html 
* http://en.wikipedia.org/wiki/JSON-RPC
* http://www.jsonrpc.org/historical/json-rpc-over-http.html
* https://groups.google.com/forum/#!searchin/json-rpc/http/json-rpc/VNIH0WaxH5U/E55v-T5iy-YJ

Examples
--------

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

Example javascript client:
```javascript
<ul>
<!-- script -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="../JsonIntiClient.js"></script>

<script>
	var url = 'http://localhost/xampp/proyectos/jsonrpcinti/examples/server.php';
	// var url = 'http://url.to/jsonrpcserver.php';

	var client = new JsonIntiClient(url);

	// Simple request
	client.sendRequest('hello', null, { 'async': false }, function(rtn) {
		if (rtn.isError()) 
			document.write('<li>Request hello error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request hello result: ' + rtn.getResult() + "</li>");
	});

	// Simple notification
	client.sendNotification('doSomething', null);

	// Params request
	client.sendRequest('addOp', [43, 21], { 'async': false }, function(rtn) {
		if (rtn.isError()) 
			document.write('<li>Request addOp error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request addOp result: ' + rtn.getResult() + "</li>");
	});

	// Unknown method
	client.sendRequest('unknown', null, { 'async': false }, function(rtn) {
		if (rtn.isError()) 
			document.write('<li>Request unknown error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request unknown result: ' + rtn.getResult() + "</li>");
	});

	// Incorrect parameters
	client.sendRequest('addOp', null, { 'async': false }, function(rtn) {
		if (rtn.isError()) 
			document.write('<li>Request addOp error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request addOp result: ' + rtn.getResult() + "</li>");
	});

	// Incorrect parameters again
	client.sendRequest('hello', 'lalala', { 'async': false }, function(rtn) {
		if (rtn.isError()) 
			document.write('<li>Request hello error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request hello result: ' + rtn.getResult() + "</li>");
	});

	// And again (custom)
	client.sendRequest('addOp', 'lalala', { 'async': false }, function(rtn) {
		if (rtn.isError()) 
			document.write('<li>Request addOp error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request addOp result: ' + rtn.getResult() + "</li>");
	});

	// Retrieving id of request and result
	client.sendRequest('addOp', [22, 71], { 'async': false, 'getid': true }, function(rtn, id) {
		document.write('<li>Request id: ' + id + "</li>");

		if (rtn.isError()) 
			document.write('<li>Request addOp (' + rtn.getId() + ') error: ' + rtn.getErrorMessage() + "</li>");
		else
			document.write('<li>Request addOp (' + rtn.getId() + ') result: ' + rtn.getResult() + "</li>");
	});

	document.write('<li>BATCH:</li>');

	// Doing a batch
	var batch = [];
	batch.push( client.constructRequest('hello') );
	batch.push( client.constructRequest('addOp', [98, 111]) );
	batch.push( client.constructNotification('doSomething') );
	batch.push( client.constructRequest('unknown') );
	batch.push( client.constructNotification('unknown') );
	batch.push( client.constructRequest('addOp', 'custom error') );
	batch.push( client.constructRequest('addOp', [3, 8], function(id) {
		document.write('<li>Last request id: ' + id + '</li>');
	}) );

	var rtn = client.sendBatch(batch, { 'async': false }, function(rtn){
		for (var i = 0; i < rtn.length; i++)
		{
			var item = rtn[i];

			if (item.isError())
				document.write('<li>Request (' + item.getId() + ') error: ' + item.getErrorMessage() + "</li>");
			else
				document.write('<li>Request (' + item.getId() + ') result: ' + item.getResult() + "</li>");
		}
	});

</script>
<!-- /script -->
</ul>
```