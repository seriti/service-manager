<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ClientLocation extends Table
{
    protected $type = ['PHYSICAL'=>'Physical: Premises location','POSTAL'=>'Postal address','INVOICE'=>'Admin: Invoice delivery'];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client location','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'contract','col_id'=>'location_id','message'=>'Contract exists using this location']);

        $this->setupMaster(['table'=>TABLE_PREFIX.'client','key'=>'client_id','child_col'=>'client_id',
                            'show_sql'=>'SELECT CONCAT("Client: ",`name`) FROM `'.TABLE_PREFIX.'client` WHERE `client_id` = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'location ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category',
                            'join'=>'`name` FROM `'.TABLE_PREFIX.'location_category` WHERE `category_id`']);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'type_id','type'=>'STRING','title'=>'Address Type']);
        $this->addTableCol(['id'=>'size','type'=>'INTEGER','title'=>'Size','required'=>false]);
        $this->addTableCol(['id'=>'address','type'=>'TEXT','title'=>'Address','required'=>false,'list'=>true]);
        $this->addTableCol(['id'=>'tel','type'=>'STRING','title'=>'Tel','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'email','type'=>'EMAIL','title'=>'Email','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'map_lat','type'=>'DECIMAL','title'=>'Map latitude','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'map_lng','type'=>'DECIMAL','title'=>'Map longitude','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.`sort`','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['location_id','category_id','name','tel','email','sort','type_id','status'],['rows'=>4]);

        $this->addSelect('category_id','SELECT `category_id`, `name` FROM `'.TABLE_PREFIX.'location_category` ORDER BY `sort`');
        
        $this->addSelect('type_id',['list'=>$this->type,'list_assoc'=>true]);
        
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);
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
