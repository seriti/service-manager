<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ServiceFeedback extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Service feedback','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'contract_visit','col_id'=>'feedback_id','message'=>'Contract visits exist for this Feedback']);

        $this->addTableCol(['id'=>'feedback_id','type'=>'INTEGER','title'=>'feedback ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'type_id','type'=>'STRING','title'=>'Type']);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort order']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.type_id, T.sort','Type, then Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['feedback_id','name','sort','status'],['rows'=>1]);

        $type = ['COMPLETE','INCOMPLETE','CLIENT_ERROR'];
        $this->addSelect('type_id',['list'=>$type,'list_assoc'=>false]);

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
