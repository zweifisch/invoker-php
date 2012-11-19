<?php

namespace Invoker;

class JavaScriptResponse extends Response
{
	function __construct($body,$status=200)
	{
		parent::__construct($body,$status,'text/javascript; charset=UTF-8');
	}
}


