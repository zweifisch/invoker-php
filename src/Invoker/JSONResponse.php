<?php

namespace Invoker;

class JSONResponse extends Response
{
	function __construct($body,$status=200)
	{
		parent::__construct(json_encode($body),$status,'application/json');
	}
}

