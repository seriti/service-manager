<?php
namespace App\Service;

use Seriti\Tools\Table;
use Seriti\Tools\Secure;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;


class ClientContact extends Table
{
    protected $type = ['PHYSICAL'=>'Staff: On premises','INVOICE'=>'Admin: Invoicing'];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client contact','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'contract','col_id'=>'contact_id','message'=>'Contract exists using this contact']);

        $this->setupMaster(['table'=>TABLE_PREFIX.'client','key'=>'client_id','child_col'=>'client_id',
                            'show_sql'=>'SELECT CONCAT("Client: ",`name`) FROM `'.TABLE_PREFIX.'client` WHERE `client_id` = "{KEY_VAL}" ']);

         
        $this->addTableCol(['id'=>'contact_id','type'=>'INTEGER','title'=>'contact ID','key'=>true,'key_auto'=>true,'list'=>true]);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location','join'=>'`name` FROM `'.TABLE_PREFIX.'client_location` WHERE `location_id`']);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'position','type'=>'STRING','title'=>'Position']);
        $this->addTableCol(['id'=>'type_id','type'=>'STRING','title'=>'Position type']);
        $this->addTableCol(['id'=>'cell','type'=>'STRING','title'=>'Cell']);
        $this->addTableCol(['id'=>'tel','type'=>'STRING','title'=>'Tel','required'=>false]);
        $this->addTableCol(['id'=>'email','type'=>'EMAIL','title'=>'Email','required'=>false]);
        $this->addTableCol(['id'=>'cell_alt','type'=>'STRING','title'=>'Cell alt','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'tel_alt','type'=>'STRING','title'=>'Tel alt','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'email_alt','type'=>'EMAIL','title'=>'Email alt','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.`sort`','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['contact_id','location_id','name','position','type_id','tel','cell','email','status'],['rows'=>4]);
        
        $this->addSelect('type_id',['list'=>$this->type,'list_assoc'=>true]);

        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    protected function beforeProcess($id = 0) 
    {
        $this->addSelect('location_id',
                         'SELECT `location_id`, `name` FROM `'.TABLE_PREFIX.'client_location` '.
                         'WHERE `client_id` = "'.$this->master['key_val'].'" ORDER BY `name`');
    }

    protected function modifyRowValue($col_id,$data,&$value)
    {
        
        if($col_id === 'type_id' and $value != '' and isset($this->type[$value])) {
            $value = $this->type[$value];
        }

    }   

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
