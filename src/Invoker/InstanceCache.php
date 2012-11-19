<?php

namespace Invoker;

class InstanceCache
{
	function __construct()
	{
		$this->cache = array();
	}

	function cacheInstance($instance,$classname,$constructArgs)
	{
		$key = md5(json_encode(array($classname,$constructArgs)));
		$this->cache[$key] = $instance;
	}

	function getInstanceFromCache($classname,$constructArgs)
	{
		$key = md5(json_encode(array($classname,$constructArgs)));
		return isset($this->cache[$key])? $this->cache[$key] : null;
	}
}


