<?php

namespace Invoker;

class Response
{
	function __construct($body,$status=200,$content_type='text/html')
	{
		$this->body = $body;
		$this->status = $status;
		$this->content_type = $content_type;
	}

	static function send($response)
	{
		if(!($response instanceof Response))
		{
			$response = new Response($response);
		}
		header('HTTP/1.1: ' . $response->status);
		header('Status: ' . $response->status);
		header('Content-Type: '. $response->content_type);

		exit($response->body);
	}
}

