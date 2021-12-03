<?php
namespace App\Service;

use Seriti\Tools\Table;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;
use Seriti\Tools\Audit;
use Seriti\Tools\Validate;

use App\Service\Helpers;

class Invoice extends Table
{
    protected $status = ['NEW'=>'New invoice','COMPLETE'=>'Processed successfully','EMAILED'=>'Emailed to client','ERROR'=>'Invoice error'];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Contract invoice','col_label'=>'contract_id'];
        parent::setup($param);

        $access = ['add'=>false,'delete'=>false,'edit'=>false];
        if(INVOICE_SETUP['allow_edit']) $access['edit'] = true;
        if($this->user_access_level === 'GOD')  $access['delete'] = true;
        $this->modifyAccess($access);

        $this->addTableCol(['id'=>'invoice_id','type'=>'INTEGER','title'=>'Invoice ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'contract_id','type'=>'INTEGER','title'=>'Contract','edit_title'=>'Contract ID','edit'=>false]);
        $this->addTableCol(['id'=>'invoice_no','type'=>'STRING','title'=>'Invoice no','edit'=>false]);
        //$this->addTableCol(['id'=>'contact_id','type'=>'INTEGER','title'=>'Contact','join'=>'`name` FROM `'.TABLE_PREFIX.'client_contact` WHERE `contact_id`']);
        
        $this->addTableCol(['id'=>'date','type'=>'DATETIME','title'=>'Date on invoice','edit'=>true,]);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal','edit'=>false]);
        $this->addTableCol(['id'=>'discount','type'=>'DECIMAL','title'=>'Discount','edit'=>false]);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax','edit'=>false]);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total','edit'=>false]);
       
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Invoice Notes','hint'=>'These will be added below last invoice item','required'=>false,'list'=>true]);
        $this->addTableCol(['id'=>'notes_admin','type'=>'TEXT','title'=>'Admin Notes','hint'=>'These will NOT appear on invoice.','required'=>false,'list'=>true]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSql('JOIN','LEFT JOIN `'.TABLE_PREFIX.'contract` AS C ON(T.`contract_id` = C.`contract_id`)');
               
        $this->addSortOrder('T.`invoice_id` DESC','Most recent first','DEFAULT');

        if($access['edit']) {
            $this->addAction(['type'=>'check_box','text'=>'']);
            $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        }    

        if($access['delete']) {
            $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);  
        }
                
        $this->addAction(['type'=>'popup','text'=>'Invoice&nbsp;items','url'=>'invoice_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['invoice_id','contract_id','invoice_no','date','total','notes','status'],['rows'=>2]);
        $this->addSearchXtra('C.client_code','Contract code');
        $this->addSearchXtra('C.division_id','Division');

        //$this->addSelect('contract_id','SELECT `contract_id`,`client_code` FROM `'.TABLE_PREFIX.'contract` ORDER BY `client_code`');
        //$this->addSelect('contact_id','SELECT `contact_id`, `name` FROM `'.TABLE_PREFIX.'client_contact` ORDER BY `name`');
              
        
        $this->addSelect('status',['list'=>$this->status,'list_assoc'=>true]);
        $this->addSelect('C.division_id','SELECT `division_id`, `name` FROM `'.TABLE_PREFIX.'division` ORDER BY `sort`');

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'INV','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'invoice_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function modifyRowValue($col_id,$data,&$value)
    {
        
        /*
        if($col_id === 'contact_id') {
            $rec = Helpers::get($this->db,TABLE_PREFIX,'client_contact',$value,'contact_id');
            $value = $rec['name'];
            if($rec['cell'] !== '') $value .= '<br/>'.$rec['cell'];
            if($rec['email'] !== '') $value .= '<br/>'.$rec['email'];

        }
        */

        if($col_id === 'contract_id') {
            $rec = Helpers::getContract($this->db,TABLE_PREFIX,$value,['get'=>'CONTRACT']);
            $value = $rec['contract']['division'].'&nbsp;('.$value.')<br/>'.
                     '<b>'.$rec['contract']['client'].'</b><br/>'.
                     'Code:<b>'.$rec['contract']['client_code'].'</b><br/>'.
                     $rec['contract']['type_id'];
        }
        
    }
    
    protected function viewTableActions() {
        $html = '';
        $list = array();
            
        $status_set = 'NEW';
        $date_set = date('Y-m-d');
        
        if(!$this->access['read_only']) {
            $list['SELECT'] = 'Action for selected '.$this->row_name_plural;
            $list['STATUS_CHANGE'] = 'Change invoice Status.';
            $list['CREATE_PDF'] = 'Create invoice PDF';
            $list['EMAIL_CLIENT'] = 'Email latest invoice PDF to Client';
            $list['EMAIL_INVOICE'] = 'Email latest invoice PDF to any address';

        }  
        
        if(count($list) != 0){
            $html .= '<span style="padding:8px;"><input type="checkbox" id="checkbox_all"></span> ';
            $param['class'] = 'form-control input-medium input-inline';
            $param['onchange'] = 'javascript:change_table_action()';
            $action_id = '';
            $status_change = 'NONE';
            $email_address = '';
            
            $html .= Form::arrayList($list,'table_action',$action_id,true,$param);
            
            //javascript to show collection list depending on selecetion      
            $html .= '<script type="text/javascript">'.
                     '$("#checkbox_all").click(function () {$(".checkbox_action").prop(\'checked\', $(this).prop(\'checked\'));});'.
                     'function change_table_action() {'.
                     'var table_action = document.getElementById(\'table_action\');'.
                     'var action = table_action.options[table_action.selectedIndex].value; '.
                     'var status_select = document.getElementById(\'status_select\');'.
                     'var email_invoice = document.getElementById(\'email_invoice\');'.
                     'status_select.style.display = \'none\'; '.
                     'email_invoice.style.display = \'none\'; '.
                     'if(action==\'STATUS_CHANGE\') status_select.style.display = \'inline\';'.
                     'if(action==\'EMAIL_INVOICE\') email_invoice.style.display = \'inline\';'.
                     '}'.
                     '</script>';
            
            $param = array();
            $param['class'] = 'form-control input-small input-inline';
            //$param['class']='form-control col-sm-3';
            $html .= '<span id="status_select" style="display:none"> status&raquo;'.
                     Form::arrayList($this->status,'status_change',$status_change,true,$param).
                     '</span>'; 
            
            $param['class'] = 'form-control input-medium input-inline';       
            $html .= '<span id="email_invoice" style="display:none"> Email address&raquo;'.
                     Form::textInput('email_address',$email_address,$param).
                     '</span>';
                    
            $html .= '&nbsp;<input type="submit" name="action_submit" value="Apply action to selected '.
                     $this->row_name_plural.'" class="btn btn-primary">';
        }  
        
        return $html; 
    }
  
    //update multiple records based on selected action
    protected function updateTable() {
        $error_str = '';
        $error_tmp = '';
        $message_str = '';
        $audit_str = '';
        $audit_count = 0;
        $html = '';
            
        $action = Secure::clean('basic',$_POST['table_action']);
        if($action === 'SELECT') {
            $this->addError('You have not selected any action to perform on '.$this->row_name_plural.'!');
        } else {
            if($action === 'STATUS_CHANGE') {
                $status_change = Secure::clean('alpha',$_POST['status_change']);
                $audit_str = 'Status change['.$status_change.'] ';
                if($status_change === 'NONE') $this->addError('You have not selected a valid status['.$status_change.']!');
            }
            
            if($action === 'EMAIL_INVOICE') {
                $email_address = Secure::clean('email',$_POST['email_address']);
                Validate::email('email address',$email_address,$error_str);
                $audit_str = 'Email invoice to['.$email_address.'] ';
                if($error_str != '') $this->addError('INVAID email address['.$email_address.']!');
            }
            
            if(!$this->errors_found) {     
                foreach($_POST as $key => $value) {
                    if(substr($key,0,8) === 'checked_') {
                        $invoice_id = substr($key,8);
                        $audit_str .= 'invoice ID['.$invoice_id.'] ';
                                            
                        if($action === 'STATUS_CHANGE') {
                            $sql = 'UPDATE `'.$this->table.'` SET `status` = "'.$this->db->escapeSql($status_change).'" '.
                                   'WHERE `invoice_id` = "'.$this->db->escapeSql($invoice_id).'" ';
                            $this->db->executeSql($sql,$error_tmp);
                            if($error_tmp === '') {
                                $message_str = 'Status set['.$status_change.'] for invoice ID['.$invoice_id.'] ';
                                $audit_str .= ' success!';
                                $audit_count++;
                                
                                $this->addMessage($message_str);                
                            } else {
                                $this->addError('Could not update status for invoice['.$invoice_id.']: '.$error_tmp);                
                            }  
                        }
                        
                    
                        if($action === 'CREATE_PDF') {
                            Helpers::createInvoicePdf($this->db,$this->container,$invoice_id,$doc_name,$error_tmp);
                            if($error_tmp === '') {
                                $audit_str .= ' success!';
                                $audit_count++;
                                $this->addMessage('Invoice['.$invoice_id.'] PDF created');      
                            } else {
                                $this->addError('Cound not create invoice['.$invoice_id.'] PDF!');
                            }   
                        }  
                        

                        if($action === 'EMAIL_INVOICE' or $action === 'EMAIL_CLIENT') {
                            if($action === 'EMAIL_CLIENT') $email_address = 'DEFAULT';
                            Helpers::sendInvoice($this->db,$this->container,$invoice_id,$email_address,$error_tmp);
                            if($error_tmp === '') {
                                $audit_str .= ' success!';
                                $audit_count++;
                                $this->addMessage('Invoice['.$invoice_id.'] sent to email['.$email_address.']');      
                            } else {
                                $this->addError('Cound not send invoice['.$invoice_id.'] to email address['.$email_address.']!');
                            }   
                        }  
                    }   
                }  
              
            }  
        }  
        
        //audit any updates except for deletes as these are already audited 
        if($audit_count != 0 and $action != 'DELETE') {
            $audit_action = $action.'_'.strtoupper($this->table);
            Audit::action($this->db,$this->user_id,$audit_action,$audit_str);
        }  
            
        $this->mode = 'list';
        $html .= $this->viewTable();
            
        return $html;
    }
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    protected function beforeDelete($id,&$error) 
    {
        if($this->user_access_level !== 'GOD') {
            $error .= 'You do not have sufficient access to delete '.$this->row_name; 
        } else {
            $error_tmp = '';
            
            //FUCKING IDIOT-> $sql = 'DELETE FROM `'.TABLE_PREFIX.'invoice_item` WHERE `item_id` = "'.$this->db->escapeSql($id).'" ';
            $sql = 'DELETE FROM `'.TABLE_PREFIX.'invoice_item` WHERE `invoice_id` = "'.$this->db->escapeSql($id).'" ';
            $this->db->executeSql($sql,$error_tmp);
            if($error_tmp == '') {
                $this->addMessage('Successfully deleted Invoice Items for '.$this->row_name.' ID['.$id.'] ');
            } else {
                $this->addError('Could not delete Invoice Items for '.$this->row_name.' ID['.$id.'] ');
            }

            $location_id = 'INV'.$id;
            $sql = 'DELETE FROM `'.TABLE_PREFIX.'file` WHERE `location_id` = "'.$this->db->escapeSql($location_id).'" ';
            $this->db->executeSql($sql,$error_tmp);
            if($error_tmp == '') {
                $this->addMessage('Successfully deleted Invoice PDF for '.$this->row_name.' ID['.$id.'] ');
            } else {
                $this->addError('Could not delete Invoice PDF for '.$this->row_name.' ID['.$id.'] ');
            } 
        }
    }

    //protected function beforeValidate($col_id,&$value,&$error,$context) {}
   

}
