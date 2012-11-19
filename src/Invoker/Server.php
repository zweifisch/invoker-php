<?php

namespace Invoker;

class Server
{
	function __construct($allowedMethods = null)
	{
		$this->allowedMethods = $allowedMethods;
		$this->instanceCache = new InstanceCache();
	}

	function get($segment,$callback)
	{
		if(Router::match($segment,'GET'))
		{
			$response = call_user_func($callback);
			$this->response($response);
		}
	}

	function post($segment,$callback)
	{
		if(Router::match($segment,'POST'))
		{
			$response = call_user_func($callback);
			$this->response($response);
		}
	}

	function listen($segment='/',$js='/classes')
	{
		$this->post($segment,function(){
			try
			{
				$result = $this->process();
				return new JSONResponse($result);
			}
			catch (HttpException $e)
			{
				return new JSONResponse($e->getMessage(), $e->getCode());
			}
			catch (ReflectionException $e)
			{
				return new JSONResponse($e->getMessage(), 403);
			}
			catch (Exception $e)
			{
				return new JSONResponse($e->getMessage(), 500);
			}
		});

		$this->get($js,function(){
			$result = $this->getClasses();
			return new JavaScriptResponse($result);
		});
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

	function call($classname,$method,$args,$constructArgs)
	{
		is_array($constructArgs) or $constructArgs = array();
		if(is_array($this->allowedMethods))
		{
			if(!isset($this->allowedMethods[$classname]))
			{
				throw new HttpException("$classname not found",404);
			}
			if(!('*' == $this->allowedMethods[$classname]) and !in_array($method,$this->allowedMethods[$classname]))
			{
				throw new HttpException("$classname->$method not found",404);
			}
		}

		$cachedInstance = $this->instanceCache->getInstanceFromCache($classname,$constructArgs);
		if($cachedInstance)
		{
			if(is_callable(array($cachedInstance,$method)))
			{
				return call_user_func_array(array($cachedInstance,$method),$args);
			}
			else
			{
				throw new HttpException("$classname->$method not callable",403);
			}
		}

		$classnameReflection = new \ReflectionClass($classname);
		$methodReflection = $classnameReflection->getMethod($method);
		if($methodReflection->isStatic())
		{
			return $methodReflection->invokeArgs(null,$args);
		}
		else if($methodReflection->isPublic())
		{
			$instance = $classnameReflection->newInstanceArgs($constructArgs);
			$this->instanceCache->cacheInstance($instance,$classname,$constructArgs);
			return $methodReflection->invokeArgs($instance,$args);
		}
		throw new HttpException("$classname->$method is not public",403);
	}

	function getClasses($namespace='classes')
	{
		$js = '';
		foreach($this->allowedMethods as $classname=>$methods)
		{
			$classReflection = new \ReflectionClass($classname);
			$publicMethods = $classReflection->getMethods(\ReflectionMethod::IS_PUBLIC);
			$staticMethods = $classReflection->getMethods(\ReflectionMethod::IS_STATIC);
			$staticMethods = array_map(function($x){return $x->name;},$staticMethods);
			$publicMethods = array_map(function($x){return $x->name;},$publicMethods);
			$publicMethods = array_filter($publicMethods,function($x){return substr($x,0,2) != '__';});

			$staticMethods = array_intersect($publicMethods,$staticMethods);
			$nonStaticMethods = array_diff($publicMethods,$staticMethods);

			if($methods != '*')
			{
				$nonStaticMethods = array_intersect($nonStaticMethods,$methods);
				$staticMethods = array_intersect($staticMethods,$methods);
			}
			$js .= "ns.$classname=invoker.getClass({name:'$classname',staticMethods:['".implode("','",$staticMethods)."'],methods:['".implode("','",$nonStaticMethods)."']});";
		}
		return '(function(){ns={};'.$js."this.$namespace=ns})()";
	}

	function response($response)
	{
		Response::send($response);
	}

}
