<?php

namespace xProduction;

class Model_Jobcard_Assigned extends Model_JobCard_Received{
	
	function init(){
		parent::init();

		// addExpression .. assigned_to .. from log

		$this->addCondition('status','assigned');
	}

	function reAssign($employee){
		$this->assignTo($employee);
	}
}	