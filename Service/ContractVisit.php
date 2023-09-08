<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ContractVisit extends Table
{
    protected $feedback_list = [];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Visit','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $sql = 'SELECT `feedback_id`, `name` FROM `'.TABLE_PREFIX.'service_feedback` ORDER BY `type_id`, `sort`';
        $this->feedback_list = $this->db->readSqlList($sql);

        $this->setupMaster(['table'=>TABLE_PREFIX.'contract','key'=>'contract_id','child_col'=>'contract_id',
                            'show_sql'=>'SELECT CONCAT("Contract: ",`client_code`) FROM `'.TABLE_PREFIX.'contract` WHERE `contract_id` = "{KEY_VAL}" ']);

        $access['read_only'] = true;                         
        $this->modifyAccess($access);

        $this->addTableCol(['id'=>'visit_id','type'=>'INTEGER','title'=>'visit ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Visit Status']);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'`name` FROM `'.TABLE_PREFIX.'visit_category` WHERE `category_id`']);
        $this->addTableCol(['id'=>'round_id','type'=>'INTEGER','title'=>'Service round','join'=>'`name` FROM `'.TABLE_PREFIX.'service_round` WHERE `round_id`']);
        $this->addTableCol(['id'=>'service_no','type'=>'STRING','title'=>'Service slip no','required'=>false]);
        $this->addTableCol(['id'=>'no_assistants','type'=>'INTEGER','title'=>'No assistants']);
        $this->addTableCol(['id'=>'date_booked','type'=>'DATETIME','title'=>'Date booked']);
        $this->addTableCol(['id'=>'date_visit','type'=>'DATETIME','title'=>'Date visit']);
        $this->addTableCol(['id'=>'time_from','type'=>'INTEGER','title'=>'Time from']);
        $this->addTableCol(['id'=>'time_to','type'=>'INTEGER','title'=>'Time to']);
        //$this->addTableCol(['id'=>'feedback_id','type'=>'INTEGER','title'=>'Feedback','join'=>'`name` FROM `'.TABLE_PREFIX.'service_feedback` WHERE `feedback_id`']);
        $this->addTableCol(['id'=>'feedback_list','type'=>'CUSTOM','title'=>'Feedback','required'=>false]);
        $this->addTableCol(['id'=>'feedback_notes','type'=>'TEXT','title'=>'Feedback Notes','required'=>false]);
        $this->addTableCol(['id'=>'feedback_user_id','type'=>'INTEGER','title'=>'Feedback User',
                            'join'=>'`'.$this->user_cols['name'].'` FROM `'.TABLE_USER.'` WHERE `'.$this->user_cols['id'].'`']);
        $this->addTableCol(['id'=>'feedback_status','type'=>'STRING','title'=>'Feedback Status','required'=>false]);
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Invoice Notes','required'=>false]);
        

        $this->addSortOrder('T.`date_visit` DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['visit_id','status','round_id','no_assistants','date_booked','date_visit',
                          'feedback_notes','feedback_status','notes'],['rows'=>4]);

        $this->addSelect('category_id','SELECT `category_id`, `name` FROM `'.TABLE_PREFIX.'visit_category` ORDER BY `sort`');
        $this->addSelect('round_id','SELECT `round_id`, `name` FROM `'.TABLE_PREFIX.'service_round` ORDER BY `sort`');
        //$this->addSelect('feedback_id','SELECT `feedback_id`, `name` FROM `'.TABLE_PREFIX.'service_feedback` ORDER BY `sort`');
        $this->addSelect('status',['list'=>VISIT_STATUS,'list_assoc'=>true]);
        $this->addSelect('feedback_status',['list'=>FEEDBACK_STATUS,'list_assoc'=>true]);

        //$sql = 'SELECT '.$this->user_cols['id'].','.$this->user_cols['name'].' FROM '.TABLE_USER.' ORDER BY '.$this->user_cols['name'];
        //$this->addSelect('feedback_user_id',$sql);
    }

    protected function modifyRowValue($col_id,$data,&$value)
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

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
