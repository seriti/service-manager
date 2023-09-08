<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;

use App\Service\Helpers;

class VisitItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Visit item','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'contract_visit','key'=>'visit_id','child_col'=>'visit_id',
                            'show_sql'=>'SELECT CONCAT("Visit ID[",V.`visit_id`,"] Contract[",C.`client_code`,"] on ",V.`date_visit`) '.
                                        'FROM `'.TABLE_PREFIX.'contract_visit` AS V JOIN `'.TABLE_PREFIX.'contract` AS C ON(V.`contract_id` = C.`contract_id`) '.
                                        'WHERE V.`visit_id` = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item',
                            'join'=>'`name` FROM `'.TABLE_PREFIX.'service_item` WHERE `item_id`']);
        $this->addTableCol(['id'=>'quantity','type'=>'INTEGER','title'=>'Quantity','new'=>1]);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price','new'=>0,'required'=>false]);
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSortOrder('T.`data_id` DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['item_id','price','notes','status'],['rows'=>2]);

        
        
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    protected function beforeProcess($id = 0) 
    {
        $sql = 'SELECT C.`division_id` FROM `'.TABLE_PREFIX.'contract_visit` AS V JOIN `'.TABLE_PREFIX.'contract` AS C ON(V.`contract_id` = C.`contract_id`) '.
               'WHERE V.`visit_id` = "'.$this->db->escapeSql($this->master['key_val']).'" ';
        $division_id = $this->db->readSqlValue($sql);       

        $this->addSelect('item_id','SELECT `item_id`, `name` FROM `'.TABLE_PREFIX.'service_item` WHERE `division_id` = "'.$division_id.'" ORDER BY `sort`');    
    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        if($data['price'] != 0 and $data['quantity'] == 0) {
            $error .= 'You have specified a '.$this->row_name.' price but quantity is = 0. If there is a price then there must be a quantity > 0.';
        }    
    }
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
