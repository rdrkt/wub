<?php
namespace Utility;
class WorkBuffer {
	private $_redis;
	private $_prefix = 'wub';
	
	private function _prefix_key(){
		$args = func_get_args();
		array_unshift($args, $this->_prefix);
		return implode('-', $args);
	}
	
	public function __construct(Redis $redis){
		$this->setRedis($redis);
	}
	
	public function setRedis(Redis $redis){
		$this->_redis = $redis;
	}
	
	public function buffer(WorkItem $work){
		$queue = $this->_prefixKey($work->getBufferName(), 'queue');
		
		$id = $work->getId();
		$this->setWorkData($key, $id, $work);
		
		//store data before queueing
		return $this->_redis->lPush($queue, $id); //'beginning' of the list is actually the last to process
	}
	
	public function hasNext($key){
		$key = $this->_prefixKey($key, 'queue');
		return ($this->_redis->lLen($key) > 0);
	}
	
	public function getNext($key, $timeout = 1000){
		$queue = $this->_prefixKey($key, 'queue' );
		$process = $this->_prefixKey($key, 'process');
		
		$id = $this->redis->bRPopLPush($queue, $process, $timeout);
		
		if($id){
			return $this->_getWorkItem($key, $id);
		}
		
		return false;
	}
	
	private function _setWorkItem($key, $id, $work){
		$keyData = $this->_prefixKey($key, 'data');
		
		$value = serialize($work);
		return $this->_redis->hSet($keyData, $id, $value);
	}
	private function _getWorkItem($key, $id){
		$keyData = $this->_prefixKey($key, 'data');
		return unserialize($this->_redis->hGet($keyData, $id));
	}
}
