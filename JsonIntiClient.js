/*
	JsonIntiClient (javascript) client and result management classes
	jsonrpcinti project - https://github.com/inticardo/jsonrpcinti

	(c) 2013 - Juan Máiquez Corbalán (Inti / Int_10h) - contacto@int10h.es
	Licensed under Apache License, Version 2.0, January 2004
	http://www.apache.org/licenses/
	
	Last changes: 2013-10-28

	This is a simple and free implementation of the JSON-RPC 2.0 specification.
	To see some examples please refer to README.md or ./examples/ directory.

	Dependences:

	- jQuery

	See also:

	- http://www.jsonrpc.org/spec.html 
	- http://en.wikipedia.org/wiki/JSON-RPC
	- http://www.jsonrpc.org/historical/json-rpc-over-http.html
	- https://groups.google.com/forum/#!searchin/json-rpc/http/json-rpc/VNIH0WaxH5U/E55v-T5iy-YJ

*/

var JsonIntiResult = function(data, iserror)
{
	iserror = typeof iserror !== 'undefined' ? iserror : false;

	this.data = data;
	this.iserror = iserror;

	if (!iserror && typeof data['error'] !== 'undefined' && typeof data['result'] === 'undefined')
		this.iserror = true;

	this.isError = function() {
		return this.iserror;
	};

	this.getErrorMessage = function() {
		if (!this.isError() || typeof this.data['error'] === 'undefined')
			return null;

		return this.data['error']['message'];
	};

	this.getErrorCode = function() {
		if (!this.isError() || typeof this.data['error'] === 'undefined')
			return null;

		return this.data['error']['code'];
	};

	this.getResult = function() {
		if (this.isError()) return null;

		return this.data['result'];
	};

	this.getId = function() {
		if (typeof this.data['id'] === 'undefined') return null;

		return this.data['id'];
	};
}

var JsonIntiClient = function(rpcserver) {
	this.VERSION = '2.0';

	this.rpcserver = rpcserver;
	this.enu = Math.floor(Math.random()*999);

	this.sendRequest = function(method, params, opts, response) {
		params = typeof params !== 'undefined' ? params : null;
		opts = typeof opts !== 'undefined' ? opts : {};

		var async = true;
		var getid = false;

		if (typeof opts['async'] !== 'undefined')
			async = opts['async'];

		if (typeof opts['getid'] !== 'undefined')
			getid = opts['getid'];

		this.enu++;
		var id = this.enu;

		var request = {
			'jsonrpc': this.VERSION,
			'method': method,
			'id': id
		};

		if (params !== null) request['params'] = params;
		
		$.ajax({
			'url': this.rpcserver,
			'data': JSON.stringify(request),
			'accepts': 'application/json',
			'contentType': 'application/json',
			'dataType': 'json',
			'async': async,
			'cache': false,
			'type': 'POST',
			'success': function(r) {
				var rtn = new JsonIntiResult(r);
				if (typeof response !== 'undefined')
				{
					if (getid) 
						response(rtn, id);
					else
						response(rtn);
				}
			}
		});
	};

	this.sendNotification = function(method, params, opts, response) {
		params = typeof params !== 'undefined' ? params : null;
		opts = typeof opts !== 'undefined' ? opts : {};

		var async = true;

		if (typeof opts['async'] !== 'undefined')
			async = opts['async'];

		var request = {
			'jsonrpc': this.VERSION,
			'method': method
		};

		if (params !== null) request['params'] = params;

		$.ajax({
			'url': this.rpcserver,
			'data': JSON.stringify(request),
			'contentType': 'application/json',
			'async': async,
			'cache': false,
			'type': 'POST',
			'success': function(r) {
				if (typeof response !== 'undefined')
					response();
			}
		});
	};

	this.constructRequest = function(method, params, getid) {
		params = typeof params !== 'undefined' ? params : null;
		
		this.enu++;
		var id = this.enu;

		var rtn = {
			'jsonrpc': this.VERSION,
			'method': method,
			'id': id
		};

		if (params !== null) rtn['params'] = params;

		if (typeof getid !== 'undefined')
			getid(id);

		return rtn;
	};

	this.constructNotification = function(method, params) {
		params = typeof params !== 'undefined' ? params : null;
		
		var rtn = {
			'jsonrpc': this.VERSION,
			'method': method,
		};

		if (params !== null) rtn['params'] = params;

		return rtn;
	};

	this.sendBatch = function(array, opts, response) {
		opts = typeof opts !== 'undefined' ? opts : {};

		var async = true;

		if (typeof opts['async'] !== 'undefined')
			async = opts['async'];
		
		$.ajax({
			'url': this.rpcserver,
			'data': JSON.stringify(array),
			'accepts': 'application/json',
			'contentType': 'application/json',
			'dataType': 'json',
			'async': async,
			'cache': false,
			'type': 'POST',
			'success': function(r) {
				if (typeof response !== 'undefined')
				{
					if (Object.prototype.toString.call(r) !== '[object Array]')
					{
						response([]);
					}
					else
					{
						var rtn = [];

						for (var i = 0; i < r.length; i++)
						{
							var item = new JsonIntiResult(r[i]);
							rtn.push(item);
						}

						response(rtn);
					}
				}
			}
		});
	};
};
