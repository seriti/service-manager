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
    protected $feedback_list = [];

    public function setup($param = []) 
    {
        $this->visit_id = Form::getVariable('id','GP',0);
        $this->round_id = Form::getVariable('round_id','GP',0);

        $mode = Form::getVariable('mode','G','');
        $edit_type = Form::getVariable('edit_type','P','NONE');

        $param = ['record_name'=>'Entry','col_label'=>'date_visit','record_id'=>$this->visit_id,'pop_up'=>true,'update_calling_page'=>true,'show_info'=>true];
        parent::setup($param); 

        $sql = 'SELECT `feedback_id`, `name` FROM `'.TABLE_PREFIX.'service_feedback` ORDER BY `type_id`, `sort`';
        $this->feedback_list = $this->db->readSqlList($sql);

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

        $this->addRecordCol(['id'=>'visit_id','type'=>'INTEGER','title'=>'Visit ID','key'=>true,'key_auto'=>true,'view'=>false]);
        $this->addRecordCol(['id'=>'status','type'=>'STRING','title'=>'Visit Status','edit'=>true]);

        if($mode === 'new' or $edit_type === 'INSERT') {
            if($mode === 'new') $this->addMessage('Create a new visit entry for any client contract allocated to round');
            
            $round = Helpers::get($this->db,TABLE_PREFIX,'service_round',$this->round_id,'round_id');
            $this->addRecordCol(['id'=>'contract_id','type'=>'INTEGER','title'=>$round['name'].' round contract',
                                      'join'=>'CONCAT("contract[",`contract_id`,"] - ",`client_code`) FROM `'.TABLE_PREFIX.'contract` WHERE `contract_id`']);
            
            $this->addSelect('contract_id','SELECT `contract_id`,CONCAT("contract[",`contract_id`,"] - ",`client_code`) FROM `'.TABLE_PREFIX.'contract` WHERE `round_id` = "'.$this->db->escapeSql($this->round_id).'" AND `status` <> "HIDE"');
        } 
        
        $this->addRecordCol(['id'=>'user_id_booked','type'=>'INTEGER','title'=>'User booked',
                                  'join'=>'CONCAT(`name`,": ",`email`) FROM `'.TABLE_USER.'` WHERE `user_id`','edit'=>false]);
        $this->addRecordCol(['id'=>'user_id_tech','type'=>'INTEGER','title'=>'Assigned technician',
                                  'join'=>'CONCAT(`name`,": ",`email`) FROM `'.TABLE_USER.'` WHERE `user_id`']);
        $this->addRecordCol(['id'=>'date_booked','type'=>'DATETIME','title'=>'Date booked','edit'=>false]);
        $this->addRecordCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category',
                                  'join'=>'`name` FROM `'.TABLE_PREFIX.'visit_category` WHERE `category_id`','edit'=>true]);
        $this->addRecordCol(['id'=>'date_visit','type'=>'DATE','title'=>'Date visit','required'=>true,'new'=>date('Y-m-d')]);
        $this->addRecordCol(['id'=>'time_from','type'=>'TIME','title'=>'Time from','required'=>true]);
        $this->addRecordCol(['id'=>'time_to','type'=>'TIME','title'=>'Time to','required'=>true]);
        $this->addRecordCol(['id'=>'no_assistants','type'=>'INTEGER','title'=>'No. assistants','edit'=>true]);
        //$this->addRecordCol(['id'=>'feedback_id','type'=>'INTEGER','title'=>'Feedback','join'=>'`name` FROM `'.TABLE_PREFIX.'service_feedback` WHERE `feedback_id`','edit'=>true]);
        $this->addRecordCol(['id'=>'feedback_list','type'=>'CUSTOM','title'=>'Feedback','required'=>false]);
        $this->addRecordCol(['id'=>'feedback_notes','type'=>'TEXT','title'=>'Feedback Notes','required'=>false]);
        $this->addRecordCol(['id'=>'feedback_user_id','type'=>'INTEGER','title'=>'Feedback User','required'=>false,
                            'join'=>'`name` FROM `'.TABLE_USER.'` WHERE `user_id`']);
        $this->addRecordCol(['id'=>'feedback_status','type'=>'STRING','title'=>'Feedback Status','required'=>false]);
        $this->addRecordCol(['id'=>'service_no','type'=>'STRING','title'=>'Service slip no','required'=>false]);
        $this->addRecordCol(['id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false]);

        $this->addAction(['type'=>'edit','text'=>'Edit','spacer'=>' - ']);
        $this->addAction(['type'=>'delete','text'=>'Delete','spacer'=>' - ']);
        
        //$status = ['NEW'=>'Preliminary booking','CONFIRMED'=>'CONFIRM booking','COMPLETED'=>'Completed visit'];
        $this->addSelect('status',['list'=>VISIT_STATUS,'list_assoc'=>true]);
        $this->addSelect('feedback_status',['list'=>FEEDBACK_STATUS,'list_assoc'=>true]);
        $this->addSelect('category_id','SELECT `category_id`, `name` FROM `'.TABLE_PREFIX.'visit_category` ORDER BY `sort`');
        //$this->addSelect('feedback_id','SELECT `feedback_id`, `name` FROM `'.TABLE_PREFIX.'service_feedback` ORDER BY `sort`');
        $this->addSelect('user_id_tech',['sql'=>'SELECT `user_id`, `name` FROM `'.TABLE_USER.'` WHERE `zone` <> "PUBLIC" AND `status` <> "HIDE" ORDER BY `name`',
                                         'xtra'=>[0=>'No Linked user']]);
        $this->addSelect('feedback_user_id',['sql'=>'SELECT `user_id`, `name` FROM `'.TABLE_USER.'` WHERE `zone` <> "PUBLIC" AND `status` <> "HIDE" ORDER BY `name`',
                                             'xtra'=>[0=>'No Linked user']]);
       
    }   

    protected function modifyRecordValue($col_id,$data,&$value)
    {
        if($col_id === 'feedback_list') {
            $value_arr = explode(',',$value);
            $html = '';
            foreach($value_arr as $feedback_id) {
               $html .= $this->feedback_list[$feedback_id].'<br/>'; 
            }
            $value = $html;
        }
        
    }

    protected function customEditValue($col_id,$value,$edit_type,$form) 
    {
        $html = '';

        if($col_id === 'feedback_list') {   
            $value_arr = explode(',',$value);

            if($this->feedback_list !== 0) {
                
                //check for changes
                foreach($this->feedback_list as $feedback_id => $name) {
                    $form_id = 'feedback_'.$feedback_id;

                    //need to account for situation where form errors and redisplay checks
                    if(count($form)) {
                        if(isset($_POST[$form_id])) $checked = true; else $checked = false;
                    } else {
                        if(in_array($feedback_id,$value_arr)) $checked = true; else $checked = false;
                    }    

                    $html .= '<li>'.Form::checkBox($form_id,true,$checked).' '.$name.'</li>';
                }

                if(count($this->feedback_list) > 5) $style = 'style="overflow: auto; height:100px;"'; else $style = '';
                $html = '<div '.$style.'>'.$html.'</div>';

            }
        }    

        return $html;
    }
    

    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $error = '';
        $calc = Date::calcMinutes($data['time_from'],$data['time_to']);
        if($calc <= 0) $error .= 'Time TO is not after time FROM. ';

        if($data['status'] === 'CONFIRMED') {
            if($data['user_id_tech'] == 0) $error .= 'You cannot Confirm a visit without assigning a technician.';    
        }

        $value_arr = [];
        foreach($this->feedback_list as $feedback_id => $name) {
            $form_id = 'feedback_'.$feedback_id;
            if(isset($_POST[$form_id])) $value_arr[] = $feedback_id;
        }

        if(count($value_arr)) {
            $data['feedback_list'] = implode(',',$value_arr);
        } else {
            $data['feedback_list'] = '';
        } 
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
