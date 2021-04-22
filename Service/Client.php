<?php
namespace App\Service;

use Seriti\Tools\Table;
use Seriti\Tools\Validate;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;

use App\Service\Helpers;

class Client extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'client_contact','col_id'=>'client_id','message'=>'Client contacts exist for this Client']);
        $this->addForeignKey(['table'=>TABLE_PREFIX.'client_location','col_id'=>'client_id','message'=>'Client locations exist for this Client']);

        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'Client ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'name FROM '.TABLE_PREFIX.'client_category WHERE category_id']);
        $this->addTableCol(['id'=>'account_code','type'=>'STRING','title'=>'Account code','required'=>false,'hint'=>'Use this to identify client with your accounting system']);
        //NB: not to be confused with Contract.client_code  Could use as any external client code
        //$this->addTableCol(['id'=>'client_code','type'=>'STRING','title'=>'Client code','required'=>false,'hint'=>'Use this to identify client with any other external system']);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name','hint'=>'This will appear in dropdown select lists']);
        $this->addTableCol(['id'=>'company_title','type'=>'STRING','title'=>'Company title','required'=>false,
                            'hint'=>'Official company title for use on invoices and other documents']);
        $this->addTableCol(['id'=>'company_no','type'=>'STRING','title'=>'Company Reg. No.','required'=>false,
                            'hint'=>'Official company registration No. for use on invoices and other documents']);
        $this->addTableCol(['id'=>'tax_reference','type'=>'STRING','title'=>'Tax reference','required'=>false,
                            'hint'=>'Official tax reference for use on invoices, normaly VAT No.']);
        $this->addTableCol(['id'=>'sales_code','type'=>'STRING','title'=>'Sales code','required'=>false,
                            'hint'=>'Sales code for use on invoices, not required']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);
      
        $this->addSortOrder('T.client_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Contacts','url'=>'client_contact','mode'=>'view','width'=>700,'height'=>600]);
        $this->addAction(['type'=>'popup','text'=>'Locations','url'=>'client_location','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['client_id','category_id','account_code','name','company_title','company_no','tax_reference','status'],['rows'=>3]);

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
    protected function viewEditXtra($id,$form,$context) 
    {
        $html = '';

        if($context === 'INSERT') {
            $param = ['class'=>$this->classes['edit']];
            $html .= '<span class="edit_label"><span class="star">*</span>Primary contact name:</span><br/>'.
                     Form::textInput('contact_name',$form['contact_name'],$param); 
            $html .= '<span class="edit_label"><span class="star">*</span>Primary contact email:</span><br/>'.
                     Form::textInput('contact_email',$form['contact_email'],$param); 
            $html .= '<span class="edit_label"><span class="star">*</span>Primary contact tel:</span><br/>'.
                     Form::textInput('contact_tel',$form['contact_tel'],$param); 
            $html .= '<span class="edit_label"><span class="star">*</span>Primary Physical address:</span><br/>'.
                     Form::textAreaInput('address_physical',$form['address_physical'],50,5,$param); 
            $html .= '<span class="edit_label">Primary Postal address:</span><br/>'.
                     Form::textAreaInput('address_postal',$form['address_postal'],50,5,$param); 

            return $html;
        }
        
        
    }

    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $error_tmp = '';
        //validate contact and location data when creating client
        if($context === 'INSERT') {
            Validate::string('Primary contact name',1,64,$_POST['contact_name'],$error_tmp);
            if($error_tmp !== '') $this->addError($error_tmp);
            Validate::string('Primary contact telephone',1,64,$_POST['contact_tel'],$error_tmp);
            if($error_tmp !== '') $this->addError($error_tmp);
            Validate::email('Primary contact email address',$_POST['contact_email'],$error_tmp);
            if($error_tmp !== '') $this->addError($error_tmp);
            Validate::string('Primary physical address',1,1000,$_POST['address_physical'],$error_tmp);
            if($error_tmp !== '') $this->addError($error_tmp);
            Validate::string('Primary postal address',0,1000,$_POST['address_postal'],$error_tmp);
            if($error_tmp !== '') $this->addError($error_tmp);
        } 

        /*
        if($data['client_code'] != '') {
            $sql = 'SELECT COUNT(*) FROM '.$this->table.' '.
                   'WHERE client_code = "'.$this->db->escapeSql($data['client_code']).'" AND client_id <> "'.$this->db->escapeSql($id).'" ';
            $count = $this->db->readSqlValue($sql);
            if($count != 0) $error .= 'Client code['.$data['client_code'].'] is NOT unique. Another client has been assigned that code.';
        }

        if($data['account_code'] != '') {
            $sql = 'SELECT COUNT(*) FROM '.$this->table.' '.
                   'WHERE account_code = "'.$this->db->escapeSql($data['account_code']).'" AND client_id <> "'.$this->db->escapeSql($id).'" ';
            $count = $this->db->readSqlValue($sql);
            if($count != 0) $error .= 'Client account code['.$data['account_code'].'] is NOT unique. Another client has been assigned that account code.';
        }
        */


    }
    protected function afterUpdate($id,$context,$data) 
    {
        if($context === 'INSERT') {
            $setup['contact_name'] = $_POST['contact_name'];
            $setup['contact_email'] = $_POST['contact_email'];
            $setup['contact_tel'] = $_POST['contact_tel'];

            $setup['address_physical'] = $_POST['address_physical'];
            $setup['address_postal'] = $_POST['address_postal'];

            Helpers::setupClient($this->db,TABLE_PREFIX,$id,$setup) ;
        } 

        if($data['account_code'] != '') {
            $sql = 'SELECT COUNT(*) FROM '.$this->table.' '.
                   'WHERE account_code = "'.$this->db->escapeSql($data['account_code']).'" AND client_id <> "'.$this->db->escapeSql($id).'" ';
            $count = $this->db->readSqlValue($sql);
            if($count != 0) {
                $msg .= 'Client account code['.$data['account_code'].'] is NOT unique. '.$count.' clients are also using that account code.';
                $this->setCache('messages',$msg);
            }    
        } 
    }
    

    protected function beforeDelete($id,&$error) 
    {
        $error_tmp = '';

        $sql = 'DELETE FROM '.TABLE_PREFIX.'client_contact WHERE client_id = "'.$this->db->escapeSql($id).'" ';
        $this->db->executeSql($sql,$error_tmp);
        if($error_tmp == '') {
            $this->addMessage('Successfully deleted contacts for '.$this->row_name.' ID['.$id.'] ');
        } else {
            $this->addError('Could not delete contacts for '.$this->row_name.' ID['.$key_id.'] ');
        } 

        $sql = 'DELETE FROM '.TABLE_PREFIX.'client_location WHERE client_id = "'.$this->db->escapeSql($id).'" ';
        $this->db->executeSql($sql,$error_tmp);
        if($error_tmp == '') {
            $this->addMessage('Successfully deleted locations for '.$this->row_name.' ID['.$id.'] ');
        } else {
            $this->addError('Could not delete locations for '.$this->row_name.' ID['.$key_id.'] ');
        } 
    }
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
