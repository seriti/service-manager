<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

use App\Service\Helpers;

class Client extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'client_contact','col_id'=>'client_id','message'=>'Client contacts exist for this Client']);
        $this->addForeignKey(['table'=>TABLE_PREFIX.'client_location','col_id'=>'client_id','message'=>'Client locations exist for this Client']);

        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'client ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'name FROM '.TABLE_PREFIX.'client_category WHERE category_id']);
        $this->addTableCol(['id'=>'client_code','type'=>'STRING','title'=>'Client code']);
        $this->addTableCol(['id'=>'account_code','type'=>'STRING','title'=>'Account code','required'=>false]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'address_physical','type'=>'TEXT','title'=>'Address physical','required'=>false]);
        $this->addTableCol(['id'=>'address_postal','type'=>'TEXT','title'=>'Address postal','required'=>false]);
        $this->addTableCol(['id'=>'tel','type'=>'STRING','title'=>'Tel','required'=>true]);
        $this->addTableCol(['id'=>'tel_alt','type'=>'STRING','title'=>'Tel alt','required'=>false]);
        $this->addTableCol(['id'=>'email','type'=>'EMAIL','title'=>'Email']);
        $this->addTableCol(['id'=>'email_alt','type'=>'EMAIL','title'=>'Email alt','required'=>false]);
        $this->addTableCol(['id'=>'invoice_no','type'=>'INTEGER','title'=>'Invoice no']);
        $this->addTableCol(['id'=>'invoice_prefix','type'=>'STRING','title'=>'Invoice prefix']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);
      

        $this->addSortOrder('T.client_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Contacts','url'=>'client_contact','mode'=>'view','width'=>700,'height'=>600]);
        $this->addAction(['type'=>'popup','text'=>'Locations','url'=>'client_location','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['client_id','category_id','client_code','account_code','name','address_physical','address_postal','tel','email','status','invoice_no','invoice_prefix'],['rows'=>3]);

        $this->addSelect('category_id','SELECT category_id, name FROM '.TABLE_PREFIX.'client_category ORDER BY sort');
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'CLTD','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'client_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

         $this->setupImages(['table'=>TABLE_PREFIX.'file','location'=>'CLTI','max_no'=>10,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>1,'storage'=>STORAGE,
                           'link_url'=>'client_image','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    protected function afterUpdate($id,$context,$data) 
    {
        Helpers::setupClient($this->db,TABLE_PREFIX,$id) ;

    }
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
