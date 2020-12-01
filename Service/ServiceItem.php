<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ServiceItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Service item','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'division','key'=>'division_id','child_col'=>'division_id',
                            'show_sql'=>'SELECT CONCAT("Division: ",name) FROM '.TABLE_PREFIX.'division WHERE division_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'item ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'code','type'=>'STRING','title'=>'Code']);
        $this->addTableCol(['id'=>'units_id','type'=>'STRING','title'=>'Units','join'=>'name FROM '.TABLE_PREFIX.'item_units WHERE units_id']);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort order']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.sort','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['item_id','name','code','units_id','sort','status'],['rows'=>2]);

        //$this->addSelect('division_id','SELECT division_id, name FROM '.TABLE_PREFIX.'division ORDER BY name');
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);
        $this->addSelect('units_id','SELECT units_id, name FROM '.TABLE_PREFIX.'item_units ORDER BY sort');

    }

    

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
