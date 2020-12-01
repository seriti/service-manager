<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

use App\Service\Helpers;

class ServiceVisit extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Contract visit','col_label'=>'contract_id'];
        parent::setup($param);

        $this->addTableCol(['id'=>'visit_id','type'=>'INTEGER','title'=>'Visit ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addTableCol(['id'=>'contract_id','type'=>'INTEGER','title'=>'Contract']);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'name FROM '.TABLE_PREFIX.'visit_category WHERE category_id']);
        $this->addTableCol(['id'=>'round_id','type'=>'INTEGER','title'=>'Service round','join'=>'name FROM '.TABLE_PREFIX.'service_round WHERE round_id']);
        $this->addTableCol(['id'=>'service_no','type'=>'STRING','title'=>'Service slip no']);
        $this->addTableCol(['id'=>'invoice_no','type'=>'STRING','title'=>'Invoice no','edit'=>false]);
        
        $this->addTableCol(['id'=>'user_id_booked','type'=>'INTEGER','title'=>'User booked','join'=>'CONCAT(name,": ",email) FROM '.TABLE_USER.' WHERE user_id','edit'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'date_booked','type'=>'DATETIME','title'=>'Date booked','edit'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'date_visit','type'=>'DATE','title'=>'Date visit','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'time_from','type'=>'TIME','title'=>'Time from','required'=>true]);
        $this->addTableCol(['id'=>'time_to','type'=>'TIME','title'=>'Time to','required'=>true]);
        $this->addTableCol(['id'=>'no_assistants','type'=>'INTEGER','title'=>'No. assistants']);
        
        $this->addTableCol(['id'=>'feedback_id','type'=>'INTEGER','title'=>'Feedback','join'=>'name FROM '.TABLE_PREFIX.'service_feedback WHERE feedback_id']);
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false,'list'=>true]);

        $this->addSql('WHERE','T.status <> "NEW" AND T.status <> "CONFIRMED" ');
               
        $this->addSortOrder('T.visit_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);
        $this->addAction(['type'=>'popup','text'=>'User&nbsp;assist','url'=>'visit_user_assist','mode'=>'view','width'=>600,'height'=>600]);
        $this->addAction(['type'=>'popup','text'=>'Service&nbsp;items','url'=>'visit_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['visit_id','status','contract_id','category_id','round_id','no_assistants','date_visit','feedback_id','notes'],['rows'=>3]);

        $this->addSelect('contract_id','SELECT contract_id,client_code FROM '.TABLE_PREFIX.'contract ORDER BY date_start DESC');
        $this->addSelect('category_id','SELECT category_id, name FROM '.TABLE_PREFIX.'visit_category ORDER BY sort');
        $this->addSelect('round_id','SELECT round_id, name FROM '.TABLE_PREFIX.'service_round ORDER BY sort');
        $this->addSelect('feedback_id','SELECT feedback_id, name FROM '.TABLE_PREFIX.'service_feedback ORDER BY type_id, sort');
        
        //$status = ['NEW'=>'Preliminary booking','CONFIRMED'=>'CONFIRM booking','COMPLETED'=>'Completed visit','INCOMPLETE'=>'NOT Completed visit','INVOICED'=>'Invoiced visit'];
        $status = ['COMPLETED'=>'Completed visit','INCOMPLETE'=>'NOT Completed visit','INVOICED'=>'Invoiced visit'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>true]);

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
            $value = $rec['contract']['division'].'<br/>'.
                     '<b>'.$rec['contract']['client'].'</b><br/>'.
                     'Code:<b>'.$rec['contract']['client_code'].'</b><br/>'.
                     $rec['contract']['type_id'];
        }
        
    }
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}
   

}
