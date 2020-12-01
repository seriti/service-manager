<?php 
namespace App\Service;

use Exception;

use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Record;
use Seriti\Tools\TABLE_USER;

use App\Service\Helpers;

class DiaryVisit Extends Record
{
    protected $visit_id;
    protected $round_id;
    protected $status_before;

    public function setup($param = []) 
    {
        $this->visit_id = Form::getVariable('id','GP',0);
        $this->round_id = Form::getVariable('round_id','GP',0);

        $mode = Form::getVariable('mode','G','');
        $edit_type = Form::getVariable('edit_type','P','NONE');

        $param = ['record_name'=>'Entry','col_label'=>'date_visit','record_id'=>$this->visit_id,'pop_up'=>true,'update_calling_page'=>true,'show_info'=>true];
        parent::setup($param); 

        if($mode === 'new') {
            $this->addState('round_id',$this->round_id);
        }

        if($this->visit_id != 0) {
            $visit = Helpers::get($this->db,TABLE_PREFIX,'contract_visit',$this->visit_id,'visit_id');
            $html = Helpers::showContract($this->db,TABLE_PREFIX,$visit['contract_id']);
            
            $this->addMessage($html['contact']);

            $this->$status_before = $visit['status'];
            if($visit['status'] === 'NEW' and $mode === '') $this->addMessage('NEW: Please confirm with client contact'); 

            $info = $html['items'].$html['visits'];
            $this->addInfo('VIEW',$info);
            $this->addInfo('EDIT',$info.'Make sure to change status accordingly if booking confirmed');   
        } else {
            $this->addInfo('EDIT','Make sure to change status accordingly if booking confirmed');
        }

        $this->changeText('btn_update','Update booking details');
        $this->changeText('btn_insert','Create new booking for a contract');

        $this->addRecordCol(array('id'=>'visit_id','type'=>'INTEGER','title'=>'Visit ID','key'=>true,'key_auto'=>true,'view'=>false));

        if($mode === 'new' or $edit_type === 'INSERT') {
            if($mode === 'new') $this->addMessage('Create a new visit entry for any client contract allocated to round');
            
            $round = Helpers::get($this->db,TABLE_PREFIX,'service_round',$this->round_id,'round_id');
            $this->addRecordCol(array('id'=>'contract_id','type'=>'INTEGER','title'=>$round['name'].' round contract','join'=>'CONCAT("contract[",contract_id,"] - ",client_code) FROM  '.TABLE_PREFIX.'contract WHERE contract_id'));
            
            $this->addSelect('contract_id','SELECT contract_id,CONCAT("contract[",contract_id,"] - ",client_code) FROM '.TABLE_PREFIX.'contract WHERE round_id = "'.$this->db->escapeSql($this->round_id).'" ');
        } 
        
        $this->addRecordCol(array('id'=>'user_id_booked','type'=>'INTEGER','title'=>'User booked','join'=>'CONCAT(name,": ",email) FROM '.TABLE_USER.' WHERE user_id','edit'=>false));
        $this->addRecordCol(array('id'=>'date_booked','type'=>'DATETIME','title'=>'Date booked','edit'=>false));
        $this->addRecordCol(array('id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'name FROM '.TABLE_PREFIX.'visit_category WHERE category_id','edit'=>true));
        $this->addRecordCol(array('id'=>'date_visit','type'=>'DATE','title'=>'Date visit','required'=>true,'new'=>date('Y-m-d')));
        $this->addRecordCol(array('id'=>'time_from','type'=>'TIME','title'=>'Time from','required'=>true));
        $this->addRecordCol(array('id'=>'time_to','type'=>'TIME','title'=>'Time to','required'=>true));
        $this->addRecordCol(array('id'=>'no_assistants','type'=>'INTEGER','title'=>'No. assistants','edit'=>true));
        $this->addRecordCol(array('id'=>'feedback_id','type'=>'INTEGER','title'=>'Feedback','join'=>'name FROM '.TABLE_PREFIX.'service_feedback WHERE feedback_id','edit'=>true));
        $this->addRecordCol(['id'=>'service_no','type'=>'STRING','title'=>'Service slip no']);
        $this->addRecordCol(array('id'=>'status','type'=>'STRING','title'=>'Status','edit'=>true));
        $this->addRecordCol(array('id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false));

        $this->addAction(array('type'=>'edit','text'=>'Edit','spacer'=>' - '));
        $this->addAction(array('type'=>'delete','text'=>'Delete','spacer'=>' - '));
        
        $status = ['NEW'=>'Preliminary booking','CONFIRMED'=>'CONFIRM booking','COMPLETED'=>'Completed visit'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>true]);
        $this->addSelect('category_id','SELECT category_id, name FROM '.TABLE_PREFIX.'visit_category ORDER BY sort');
        $this->addSelect('feedback_id','SELECT feedback_id, name FROM '.TABLE_PREFIX.'service_feedback ORDER BY sort');
       
    }   


    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $error = '';
        $calc = Date::calcMinutes($data['time_from'],$data['time_to']);
        if($calc <= 0) $error .= 'Time TO is not after time FROM';

    }

    protected function afterUpdate($id,$context,$data) 
    {
        $error = '';

        $update = [];
        //if($this->$status_before === 'NEW' and $data['status'] === 'CONFIRMED') {
        if($data['status'] === 'CONFIRMED') {
            $update['user_id_booked'] = $this->user_id;
            $update['date_booked'] = date('Y-m-d H:i:s');
        }  
        
        if($context === 'INSERT') {
            $update['round_id'] = $this->round_id;
        }  

        if(count($update)) {
            $where['visit_id'] = $id;
            $this->db->updateRecord($this->table,$update,$where,$error);
        }

        if($error !== '') throw new Exception('CONTRACT_VISIT_UPDATE: Could not update diary booking details.');
    }  
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {} 
    //protected function beforeValidate($col_id,&$value,&$error,$context) {} 
        

}
?>
