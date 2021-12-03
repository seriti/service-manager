<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class AccountCode extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Code','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'code_id','type'=>'INTEGER','title'=>'Code ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'code','type'=>'STRING','title'=>'Accounting code']);
        $this->addTableCol(['id'=>'description','type'=>'STRING','title'=>'Invoice code description']);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort order']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.`sort`','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['code','description','sort','status'],['rows'=>1]);

        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
