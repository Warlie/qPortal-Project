<?php
require_once('classes/exceptions/object_block_exception.php');
require_once('classes/exceptions/programm_block_exception.php');
require_once('classes/exceptions/no_permission_exception.php');
require_once('classes/exceptions/wrong_class_exception.php');

class ExceptionCollection
{
	private $mtNS;
	private $list;
	private $counter = 0;

	
	function __construct (&$multitreeNS)
	{
	
		$this->mtNS = &$multitreeNS;
		$this->list = array();
		
	
	}	
	
	public function catchException($exception)
	{
		$this->list[] = &$exception;
		//$exception->getTraceAsString ( );
	}
	
	public function many(){return count($this->list);}
	
	public function first(){$this->counter = 0;}
	public function next(){if($this->counter < count($this->list))$this->counter++;}
	public function EOF(){return !($this->counter < count($this->list));}
	public function &getException(){return $this->list[$this->counter];}
	
}
?>