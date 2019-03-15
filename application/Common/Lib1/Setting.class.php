<?php
namespace Common\Lib;

class Setting{

	public static $_instance = false;

	private $_setting = array(
		// '1_1' = array(
		// 	'key' => false,
		// ),
	);

	private function __construct($configId, $stationId){
		$data = M()->find();
		$this->_setting[$configId.'_'.$stationId] = array();
	}

	public static function getInstance($configId, $stationId){
		if($configId && $stationId){
			if(!self::$_instance instanceof Self){
				self::$_instance = new Self($configId, $stationId);
			}
			return self::$_instance;
		}else{
			throw new \Exception("Error Processing Request", 1);
		}
	}

	public static function initSetting($configId, $stationId){
		if(!array_key_exists($configId.'_'.$stationId, $this->_setting)){
			$data = M()->find();
			$this->_setting = array();
		}
			
	}

	public function __get($key){
		return $this->_setting[$key] ? $this->_setting[$key] : false;
	}
}
