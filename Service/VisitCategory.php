<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class VisitCategory extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Visit category','row_name_plural'=>'Visit categories','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'contract_visit','col_id'=>'category_id','message'=>'Contract visits exist for this Category']);

        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort order']);
        $this->addTableCol(['id'=>'chargeable','type'=>'BOOLEAN','title'=>'Chargeable']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.`sort`','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['category_id','name','sort','chargeable','status'],['rows'=>1]);

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
