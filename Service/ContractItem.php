<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;

use App\Service\Helpers;

class ContractItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Contract item','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'contract','key'=>'contract_id','child_col'=>'contract_id',
                            'show_sql'=>'SELECT CONCAT("Contract: ",client_code) FROM '.TABLE_PREFIX.'contract WHERE contract_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item','join'=>'name FROM '.TABLE_PREFIX.'service_item WHERE item_id']);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price','new'=>0,'required'=>false]);
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['item_id','price','notes','status'],['rows'=>2]);

        
        
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    protected function beforeProcess($id) 
    {
        $contract = Helpers::get($this->db,TABLE_PREFIX,'contract',$this->master['key_val']);
        $this->addSelect('item_id','SELECT item_id, name FROM '.TABLE_PREFIX.'service_item WHERE division_id = "'.$contract['division_id'].'" ORDER BY sort');    
    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
