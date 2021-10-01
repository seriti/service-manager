<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Division extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Division','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'division_id','type'=>'INTEGER','title'=>'Division ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort','hint'=>'Determines position in dropdown select lists']);
        $this->addTableCol(['id'=>'invoice_title','type'=>'STRING','title'=>'Invoice title','hint'=>'Business title on invoice top left']);
        $this->addTableCol(['id'=>'invoice_address','type'=>'TEXT','title'=>'Invoice address','list'=>true,
                            'hint'=>'Business address detals below left of invoice title']);
        $this->addTableCol(['id'=>'invoice_contact','type'=>'TEXT','title'=>'Invoice contact','list'=>true,
                            'hint'=>'Business contact details below right of invoice title']);
        $this->addTableCol(['id'=>'invoice_info','type'=>'TEXT','title'=>'Invoice info','list'=>true,
                            'hint'=>'Business banking details and other info left of invoice totals']);
        $this->addTableCol(['id'=>'invoice_prefix','type'=>'STRING','title'=>'Invoice prefix','hint'=>'Text to appear before invoice No.']);
        $this->addTableCol(['id'=>'invoice_no','type'=>'INTEGER','title'=>'Invoice no','hint'=>'Incremental invoice number','edit'=>false]);
        $this->addTableCol(['id'=>'contract_prefix','type'=>'STRING','title'=>'Contract prefix','hint'=>'Text to appear before Contract No.','required'=>false]);
        $this->addTableCol(['id'=>'contract_no','type'=>'INTEGER','title'=>'Contract no','hint'=>'Incremental contract number','edit'=>false]);
        
        $this->addTableCol(['id'=>'tax_free','type'=>'BOOLEAN','title'=>'Tax free']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.sort','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Service&nbsp;Items','url'=>'service_item','mode'=>'view','width'=>600,'height'=>600]);
        $this->addAction(['type'=>'popup','text'=>'Service&nbsp;Pricing','url'=>'service_price','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['name','sort','status'],['rows'=>1]);

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
