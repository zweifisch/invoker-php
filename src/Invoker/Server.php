<?php

namespace Invoker;

class Server
{
	function __construct($allowedMethods = null)
	{
		$this->allowedMethods = $allowedMethods;
		$this->instanceCache = array();
	}

	function listen($segment = '/gateway')
	{
		$path = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		if($segment == $path)
		{
			$this->json_response($this->process());
		}
	}

	function process()
	{
		$calls = json_decode(file_get_contents('php://input'),true);
		$result = array();
		foreach($calls as $call)
		{
			list($classname,$method,$args,$constructArgs) = $call;
			$result[] = $this->call($classname,$method,$args,$constructArgs);
		}
		return $result;
	}

	function cacheInstance($instance,$classname,$constructArgs)
	{
		$key = md5(json_encode(array($classname,$constructArgs)));
		$this->instanceCache[$key] = $instance;
	}

	function getInstanceFromCache($classname,$constructArgs)
	{
		$key = md5(json_encode(array($classname,$constructArgs)));
		return isset($this->instanceCache[$key])? $this->instanceCache[$key] : null;
	}

	function call($classname,$method,$args,$constructArgs)
	{
		is_array($constructArgs) or $constructArgs = array();
		if(is_array($this->allowedMethods))
		{
			if(!isset($this->allowedMethods[$classname]))
			{
				$this->json_response("$classname",403);
			}
			if(!('*' == $this->allowedMethods[$classname]) and !in_array($method,$this->allowedMethods[$classname]))
			{
				$this->json_response("$classname->$method",403);
			}
		}

		$cachedInstance = $this->getInstanceFromCache($classname,$constructArgs);
		if($cachedInstance)
		{
			if(is_callable(array($cachedInstance,$method)))
			{
				return call_user_func_array(array($cachedInstance,$method),$args);
			}
			else
			{
				$this->json_response("$classname->$method not callable",403);;
			}
		}

		try
		{
			$classnameReflection = new \ReflectionClass($classname);
			$methodReflection = $classnameReflection->getMethod($method);
			if($methodReflection->isStatic())
			{
				return $methodReflection->invokeArgs(null,$args);
			}
			else if($methodReflection->isPublic())
			{
				$instance = $classnameReflection->newInstanceArgs($constructArgs);
				$this->cacheInstance($instance,$classname,$constructArgs);
				return $methodReflection->invokeArgs($instance,$args);
			}
			$this->json_response("$classname->$method is not public",403);;
		}
		catch (ReflectionException $e)
		{
			return $e->getMessage();
		}
	}

	function json_response($data,$http_code = 200){
		is_numeric($http_code) or $http_code = 200;
		$output = json_encode($data);
		header('HTTP/1.1: ' . $http_code);
		header('Status: ' . $http_code);
		header('Content-Type: application/json');
		exit($output);
	}

}
