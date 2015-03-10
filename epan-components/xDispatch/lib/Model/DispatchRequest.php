<?php
namespace xStore;

class Model_DispatchRequest extends \xProduction\Model_JobCard {
	
	public $table = 'xstore_dispatch_request';

	public $root_document_name='xStore\DispatchRequest';
	public $status = array('draft','submitted','approved','assigned','processing','processed','forwarded',
							'complete','cancel','return');

	function init(){
		parent::init();

		$this->addCondition('type','DispatchRequest');

		$this->getElement('status')->defaultValue('submitted');

		$this->hasMany('xStore/DispatchRequestItem','dispatch_request_id');
		$this->hasMany('xStore/StockMovement','dispatch_request_id');

		$this->addHook('beforeInsert',$this);

		$this->add('dynamic_model/Controller_AutoCreator');
	}

	function beforeInsert($obj){
		$obj['name'] = rand(1000,9999);
	}

	function relatedChallan(){
		$challan =  $this->ref('xStore/StockMovement')->tryLoadAny();
		if($challan->loaded()) return $challan;

		return false;
	}


	// Called if direct order to store is required
	function createFromOrder($order_item, $order_dept_status ){
		$new_request = $this->add('xDispatch/Model_DispatchRequest');
		$new_request->addCondition('orderitem_id',$order_item->id);
		$new_request->tryLoadAny();

		if(!$new_request->loaded()){
			// Create Request From Next Department In Phases :: IMPORTANT

			$from_dept_status = $order_item->nextDeptStatus($order_dept_status->department());
			$from_dept = $from_dept_status->department();
			$new_request->create(
					$from_dept,
					$order_dept_status->department(),
					$related_document=$order_item->order(), 
					$order_item, 
					$items_array=array(), 
					
				);
			$new_request['status']='approved'; // AUTO CREATED AND CONSIDERED APPROVED
			$new_request->save();
		}

		$new_request->addItem($order_item->ref('item_id'),$order_item['qty']);

	}

	function addItem($item,$qty,$unit='Nos'){
		$mr_item = $this->ref('xStore/MaterialRequestItem');
		$mr_item['item_id'] = $item->id;
		$mr_item['qty'] = $qty;
		$mr_item['unit'] = $unit;
		$mr_item->save();
	}

	function mark_processed_page($page){

		
		$page->add('View')->set('stock se minus kar do ... status processed kar do');
	}

	function submit_page($p){
		$p->add('View')->set('Hello');
		$form = $p->add('Form');
		$form->addSubmit();

		if($form->isSubmitted()){
			$this->setStatus('submitted');
			return true;
		}
		return false;
	}

	function accept_page($p){
		$p->add('View_Success')->set('Show Related Challan HEre');
		$form = $p->add('Form');
		$accept_btn = $form->addSubmit('Accept');
		$reject_btn = $form->addSubmit('Reject');

		if($form->isSubmitted()){
			if($form->isClicked($accept_btn)){
				$this->relatedChallan()->acceptMaterial();
				$this->setStatus('completed');
			}else{
				throw new \Exception("Rejected", 1);
			}
		}
	}

	function setStatus($status){
		if($this['orderitem_id']){
			$ds = $this->orderItem()->deptartmentalStatus($this->toDepartment());
			$ds->setStatus(ucwords($status) .' in ' . $this['department']);
		}
		$this['status']=$status;
		$this->saveAs('xStore/Model_MaterialRequest');
	}


}