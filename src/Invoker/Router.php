<?php

namespace Invoker;

class Router
{
	static function match($uri,$method=null,$ajax=null)
	{
		if(!is_null($method))
		{
			if(strtoupper($_SERVER['REQUEST_METHOD']) !== $method) return false;
		}

		if(!is_null($ajax))
		{
			$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
			if($ajax != $is_ajax) return false;
		}

		$path = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		return $uri === $path;
	}
}


