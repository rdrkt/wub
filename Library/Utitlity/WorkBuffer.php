<?php
namespace Utility;
class WorkBuffer {
	private $_redis;
	
	public function __construct(Redis $redis){
		$this->setRedis($redis);
	}
	
	public function setRedis(Redis $redis){
		$this->_redis = $redis;
	}
	
	public function buffer(WorkItem $work){
		$key = $work->getBufferName();
		$value = (string) $work;
		$priority = $work->getPriority();
		
		$this->_redis->zAdd($key, $value, $priority);
	}
	
	public function hasNext($key){
		return ($this->_redis->zCard($key) > 0);
	}
	
	public function getNext($key){
		$item = $this->redis->zRevRange($key, 0, 0);
		$this->redis->zRem($item);
		return new WorkItem($item);
	}
}
