<?php

namespace xStore;

class Model_MaterialRequestSent_Processed extends Model_MaterialRequestSent{
	
	function init(){
		parent::init();
		$this->addCondition('status','processed');
	}
}	