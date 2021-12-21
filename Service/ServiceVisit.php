<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;

use App\Service\Helpers;

class ServiceVisit extends Table
{
    protected $feedback_list = [];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Contract visit','col_label'=>'contract_id'];
        parent::setup($param);

        $sql = 'SELECT `feedback_id`, `name` FROM `'.TABLE_PREFIX.'service_feedback` ORDER BY `type_id`, `sort`';
        $this->feedback_list = $this->db->readSqlList($sql);

        $this->addTableCol(['id'=>'visit_id','type'=>'INTEGER','title'=>'Visit ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Visit Status']);

        $this->addTableCol(['id'=>'contract_id','type'=>'INTEGER','title'=>'Contract','edit_title'=>'Contract ID']);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category',
                            'join'=>'`name` FROM `'.TABLE_PREFIX.'visit_category` WHERE `category_id`']);
        $this->addTableCol(['id'=>'round_id','type'=>'INTEGER','title'=>'Service round',
                            'join'=>'`name` FROM `'.TABLE_PREFIX.'service_round` WHERE `round_id`']);
        $this->addTableCol(['id'=>'service_no','type'=>'STRING','title'=>'Service slip no','required'=>false]);
        $this->addTableCol(['id'=>'invoice_no','type'=>'STRING','title'=>'Invoice no','edit'=>false]);
        
        $this->addTableCol(['id'=>'user_id_booked','type'=>'INTEGER','title'=>'User Booked',
                            'join'=>'CONCAT(`name`,": ",`email`) FROM `'.TABLE_USER.'` WHERE `user_id`','edit'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'user_id_tech','type'=>'INTEGER','title'=>'Assigned technician',
                            'join'=>'CONCAT(`name`,": ",`email`) FROM `'.TABLE_USER.'` WHERE `user_id`']);
        $this->addTableCol(['id'=>'date_booked','type'=>'DATETIME','title'=>'Date booked','edit'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'date_visit','type'=>'DATE','title'=>'Date visit','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'time_from','type'=>'TIME','title'=>'Time from','required'=>true]);
        $this->addTableCol(['id'=>'time_to','type'=>'TIME','title'=>'Time to','required'=>true]);
        $this->addTableCol(['id'=>'no_assistants','type'=>'INTEGER','title'=>'No. assistants']);
        
        //$this->addTableCol(['id'=>'feedback_id','type'=>'INTEGER','title'=>'Feedback','join'=>'`name` FROM `'.TABLE_PREFIX.'service_feedback` WHERE `feedback_id`']);
        $this->addTableCol(['id'=>'feedback_list','type'=>'CUSTOM','title'=>'Feedback','required'=>false]);
        $this->addTableCol(['id'=>'feedback_notes','type'=>'TEXT','title'=>'Feedback Notes','required'=>false]);
        $this->addTableCol(['id'=>'feedback_status','type'=>'STRING','title'=>'Feedback Status','required'=>false]);
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false,'list'=>true]);

        //$this->addSql('WHERE','T.status <> "NEW" AND T.status <> "CONFIRMED" ');
        $this->addSql('JOIN','LEFT JOIN `'.TABLE_PREFIX.'contract` AS C ON(T.`contract_id` = C.`contract_id`)');
        $this->addSql('JOIN','LEFT JOIN `'.TABLE_PREFIX.'client` AS CL ON(C.`client_id` = CL.`client_id`)');
               
        $this->addSortOrder('T.`visit_id` DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);
        $this->addAction(['type'=>'popup','text'=>'User&nbsp;assist','url'=>'visit_user_assist','mode'=>'view','width'=>600,'height'=>600]);
        $this->addAction(['type'=>'popup','text'=>'Service&nbsp;items','url'=>'visit_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['visit_id','status','contract_id','category_id','round_id','user_id_tech','no_assistants','date_visit',
                          'feedback_notes','feedback_status','notes'],['rows'=>4]);
        $this->addSearchXtra('C.client_code','Contract code');
        $this->addSearchXtra('C.division_id','Division');
        $this->addSearchXtra('CL.name','Client name');

        //$this->addSelect('contract_id','SELECT `contract_id`,`client_code` FROM `'.TABLE_PREFIX.'contract` WHERE `status` <> "HIDE" ORDER BY `client_code`');
        $this->addSelect('category_id','SELECT `category_id`, `name` FROM `'.TABLE_PREFIX.'visit_category` ORDER BY `sort`');
        $this->addSelect('round_id','SELECT `round_id`, `name` FROM `'.TABLE_PREFIX.'service_round` ORDER BY `sort`');
        //$this->addSelect('feedback_id','SELECT `feedback_id`, `name` FROM `'.TABLE_PREFIX.'service_feedback` ORDER BY `type_id`, `sort`');
        $this->addSelect('C.division_id','SELECT `division_id`, `name` FROM `'.TABLE_PREFIX.'division` ORDER BY `sort`');
        
        //$status = ['NEW'=>'Preliminary booking','CONFIRMED'=>'CONFIRM booking','COMPLETED'=>'Completed visit','INCOMPLETE'=>'NOT Completed visit','INVOICED'=>'Invoiced visit'];
        $this->addSelect('status',['list'=>VISIT_STATUS,'list_assoc'=>true]);
        $this->addSelect('feedback_status',['list'=>FEEDBACK_STATUS,'list_assoc'=>true]);
        $this->addSelect('user_id_tech','SELECT `user_id`, `name` FROM `'.TABLE_USER.'` WHERE `zone` <> "PUBLIC" AND `status` <> "HIDE" ORDER BY `name`');

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'VST','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'visit_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function modifyRowValue($col_id,$data,&$value)
    {
        
        if($col_id === 'contact_id') {
            $rec = Helpers::get($this->db,TABLE_PREFIX,'client_contact',$value,'contact_id');
            $value = $rec['name'];
            if($rec['cell'] !== '') $value .= '<br/>'.$rec['cell'];
            if($rec['email'] !== '') $value .= '<br/>'.$rec['email'];

        }

        if($col_id === 'contract_id') {
            $rec = Helpers::getContract($this->db,TABLE_PREFIX,$value,['get'=>'CONTRACT']);
            $value = $rec['contract']['division'].'&nbsp;('.$value.')<br/>'.
                     '<b>'.$rec['contract']['client'].'</b><br/>'.
                     'Code:<b>'.$rec['contract']['client_code'].'</b><br/>'.
                     $rec['contract']['type_id'];
        }

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
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}
   

}
