<?php
namespace Utility;
abstract class WorkItem implements \Serializable {
	private $_buffer;
	protected $_bufferName = false;
	protected $_id;
	protected $_data;
	
	public function getId(){
		return $this->_id ?: $this->_id = (string) new \MongoId();
	}
	public function getBufferName(){
		return $this->_bufferName ?: get_class($this);
	}
	
	public function setBufferName($name){
		$this->_bufferName = $name;
	}
	
	public function serialize(){
		$package = array(
			'id' => $this->_id,
			'bufferName' => $this->_bufferName,
			'data' => $this->_data,
		);
		
		return json_encode($package);
	}
	public function unserialize($data){
		$package = json_decode($data, true);
		
		$this->_id = $package['id'];
		$this->_bufferName = $package['bufferName'];
		$this->_data = $package['data'];
	}
	
	public function setBuffer(WorkBuffer $buffer){
		$this->_buffer = $buffer;
	}
	
	public function getBuffer(){
		if(empty($this->_buffer)){
			throw new WorkException("This method cannot be called unless the WorkItem has been retrieved from a WorkBuffer.");
		}
		
		return $this->_buffer;
	}
	
	public function touch(){
		$this->getBuffer()->touch($this);
	}
	
	public function release(){
		$this->getBuffer()->release($this);
	}
	
	public function complete(){
		$this->getBuffer()->complete($this);
	}
	
	public function fail(){
		$this->getBuffer()->fail($this);
	}
	
	public function __construct($data, $options = array()){
		$this->_data = $data;
	}
	abstract public function process();
	abstract public function isValid();
}
