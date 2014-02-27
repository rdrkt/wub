<?php
namespace Utility;
class WorkBuffer {
	private $_redis;
	private $_prefix = 'wub';
	
	private function _prefixKey(){
		$args = func_get_args();
		array_unshift($args, $this->_prefix);
		return implode('-', $args);
	}
	
	public function __construct(\Redis $redis){
		$this->setRedis($redis);
	}
	
	public function setRedis(\Redis $redis){
		$this->_redis = $redis;
	}
	
	public function save(WorkItem $work){
		$queue = $this->_prefixKey($work->getBufferName(), 'queue');
		
		$this->_addBuffer($work->getBufferName());
		$this->_setWorkItem($work);//store data before queueing
		
		return $this->_redis->lPush($queue, $work->getId()); //'beginning' of the list is actually the last to process
	}
	
	public function hasNext($key){
		$key = $this->_prefixKey($key, 'queue');
		
		return ($this->_redis->lLen($key) > 0);
	}
	
	public function reserve($key, $timeout = 1000, $ttl = 3600){
		$process = $this->_prefixKey($key, 'process');
		
		$queue = $this->_prefixKey($key, 'queue' );
		$id = $this->_redis->bRPopLPush($queue, $process, $timeout);
		
		if($id){
			$this->_addBuffer($key);
			$work = $this->_getWorkItem($key, $id);
			
			$this->touch($work, $ttl);
			
			return $work;
		}
		
		return false;
	}
	
	public function touch($work, $ttl = 3600){
		$touch =  $this->_prefixKey('touch', $work->getId());
		
		$this->_redis->setnx($touch, date('c'));
		
		if($ttl > 0){
			$this->_redis->pexpire($touch, $ttl);
		}
	}
	
	public function getTtl($work){
		$touch =  $this->_prefixKey('touch', $work->getId());
		
		return $this->_redis->pttl();
	}
	
	public function complete(WorkItem $work){
		$key = $work->getBufferName();
		$id = $work->getId();
		
		$queue = $this->_prefixKey($key, 'queue');
		$process = $this->_prefixKey($key, 'process');
		$touch =  $this->_prefixKey('touch', $id);
		
		$this->_removeWorkItem($work);
		$this->_redis->del($touch); //delete the 'lease' on this work item
		
		$this->_redis->lRem($queue, $id); //in case it went back into the queue.
		
		return $this->_redis->lRem($process, $work->getId());
	}
	
	public function release(WorkItem $work){
		$key = $work->getBufferName();
		$process = $this->_prefixKey($key, 'process');
		$queue = $this->_prefixKey($key, 'queue');
		$touch =  $this->_prefixKey('touch', $id);
		
		$this->_redis->lRem($process, $work->getId());
		$this->_redis->del($touch); //delete the 'lease' on this work item
		
		return $this->_redis->lPush($queue, $work->getId());
	}
	
	public function fail(WorkItem $work){
		$key = $work->getBufferName();
		$process = $this->_prefixKey($key, 'process');
		$fail = $this->_prefixKey($key, 'fail');
		
		$this->_redis->lRem($process, $work->getId());
		
		return $this->_redis->lPush($fail, $work->getId());
	}
	
	public function getBufferNames(){
		$list = $this->_prefixKey('bufferNames');
		
		$names = array();
		foreach($this->_redis->sMembers($list) as $index => $key){
			$queue = $this->_prefixKey($key, 'queue');
			$process = $this->_prefixKey($key, 'process');
			$stale = $this->_prefixKey($key, 'stale');
			$fail = $this->_prefixKey($key, 'fail');
			
			if(!$this->_redis->lLen($queue) && !$this->_redis->lLen($process) && !$this->_redis->lLen($stale) && !$this->_redis->lLen($fail)){
				$this->_removeBuffer($key); //no jobs left anywhere, so remove from the list of buffers
			} else {
				$names[] = $key;
			}
		}
		
		return $names;
	}
	
	private function _addBuffer($key){
		$list = $this->_prefixKey('bufferNames');
		
		return $this->_redis->sAdd($list, $key);
	}
	
	private function _removeBuffer($key){
		$list = $this->_prefixKey('bufferNames');
		
		return $this->_redis->sRem($list, $key);
	}
	
	private function _setWorkItem(WorkItem $work){
		$id = $work->getId();
		$keyData = $this->_prefixKey($work->getBufferName(), 'data');
		
		$value = serialize($work);
		
		return $this->_redis->hSet($keyData, $id, $value);
	}
	private function _getWorkItem($key, $id){
		$keyData = $this->_prefixKey($key, 'data');
		
		$data = $this->_redis->hGet($keyData, $id);
		
		$work = unserialize($data);
		$work->setBuffer($this);
		
		return $work;
	}
	
	private function _removeWorkItem(WorkItem $work){
		$keyData = $this->_prefixKey($work->getBufferName(), 'data');
		
		return $this->_redis->hDel($keyData, $work->getId());
	}
}
