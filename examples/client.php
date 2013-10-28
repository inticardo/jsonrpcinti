<pre>
<?php
	include '../class.JsonIntiClient.inc.php';

	$url = 'http://localhost/xampp/proyectos/jsonrpcinti/examples/server.php';
	// $url = 'http://url.to/jsonrpcserver.php';

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
		echo 'Request addOp ('.$rtn->getId().') error: '.$rtn->getErrorMessage()."\n";
	else
		echo 'Request addOp ('.$rtn->getId().') result: '.$rtn->getResult()."\n";

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

	foreach ($rtn as $item)
	{
		if ($item->isError())
			echo 'Request ('.$item->getid().') error: '.$item->getErrorMessage()."\n";
		else
			echo 'Request ('.$item->getid().') result: '.$item->getResult()."\n";
	}

?>
</pre>