<?php
namespace App\Service;

use Exception;

use Seriti\Tools\Wizard;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Validate;
use Seriti\Tools\Doc;
use Seriti\Tools\Calc;
use Seriti\Tools\Secure;
use Seriti\Tools\Plupload;
use Seriti\Tools\STORAGE;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_TEMP;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\TABLE_USER;
use Seriti\Tools\SITE_NAME;

use App\Service\Helpers;


class InvoiceWizard extends Wizard 
{
    protected $user;
    protected $user_id;
    protected $table_prefix;
    
    //configure
    public function setup($param = []) 
    {
        $this->table_prefix = TABLE_PREFIX;
        
        $this->user = $this->getContainer('user');
        $this->user_id = $this->user->getId();

        $param['bread_crumbs'] = true;
        $param['strict_var'] = false;
        $param['csrf_token'] = $this->getContainer('user')->getCsrfToken();
        parent::setup($param);

        $date_last_visit =  date('Y-m-d',mktime(0,0,0,date('m')-1,1,date('Y')));

        $this->addVariable(array('id'=>'division_id','type'=>'INTEGER','title'=>'Division','required'=>true));
        $this->addVariable(array('id'=>'type_id','type'=>'STRING','title'=>'Contract type','required'=>true,'new'=>'REPEAT'));
        $this->addVariable(array('id'=>'round_id','type'=>'INTEGER','title'=>'Agent','required'=>true));
        $this->addVariable(array('id'=>'date_last_invoice','type'=>'DATE','title'=>'Date last invoiced','required'=>true,'new'=>$date_last_visit));
        $this->addVariable(array('id'=>'client_name','type'=>'STRING','title'=>'Client name','required'=>false));
        $this->addVariable(array('id'=>'client_code','type'=>'STRING','title'=>'Client Contract code','required'=>false));
        $this->addVariable(array('id'=>'invoice_type','type'=>'STRING','title'=>'Invoice type','new'=>'STANDARD'));

        /*
        $this->addVariable(array('id'=>'client_id','type'=>'INTEGER','title'=>'Client','required'=>true));
        $this->addVariable(array('id'=>'contract_code','type'=>'STRING','title'=>'Contract code','required'=>true));

        $this->addVariable(array('id'=>'location_id','type'=>'INTEGER','title'=>'Client location address','required'=>true));
        $this->addVariable(array('id'=>'date_receive','type'=>'DATE','title'=>'Date','required'=>true,'new'=>date('Y-m-d')));
        $this->addVariable(array('id'=>'note','type'=>'TEXT','title'=>'notes','required'=>false));
        
        $this->addVariable(array('id'=>'item_count','type'=>'INTEGER','title'=>'Item count','required'=>false));
        $this->addVariable(array('id'=>'confirm_action','type'=>'STRING','title'=>'Confirmation action','new'=>'EMAIL'));
        $this->addVariable(array('id'=>'confirm_email','type'=>'EMAIL','title'=>'Confirmation email address','required'=>true));
        */

        //define pages and templates
        $this->addPage(1,'Setup','service/invoice_page1.php',['go_back'=>true]);
        $this->addPage(2,'Review and process invoices','service/invoice_page2.php');
        $this->addPage(3,'Summary','service/invoice_page3.php',['final'=>true]);
            

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //get contract list for processing
        if($this->page_no == 1) {
            $today = new \DateTime();
            
            $invoice_type = $this->form['invoice_type'];
            $division_id = $this->form['division_id'];
            $type_id = $this->form['type_id'];
            $round_id = $this->form['round_id'];
            $date_last_invoice = $this->form['date_last_invoice'];
            $client_name = trim($this->form['client_name']);
            $client_code = trim($this->form['client_code']);
            
            if($type_id === 'SINGLE' and $invoice_type === 'AUDIT') {
                $this->addError('Single contracts cannot have invoices for audit fees ');
            }


            $table_contract = $this->table_prefix.'contract';
            $table_client = $this->table_prefix.'client';
            $table_visit = $this->table_prefix.'contract_visit';
            $table_invoice = $this->table_prefix.'contract_invoice';
            
            $this->data['invoice_type'] = $invoice_type;            
            $this->data['division'] = Helpers::get($this->db,$this->table_prefix,'division',$division_id);
            $this->data['round'] = Helpers::get($this->db,$this->table_prefix,'service_round',$round_id,'round_id');
            

            //NB: expecting 1:monday/2:tuesday....etc
            $sql = 'SELECT `day_id`,LOWER(`name`) FROM `'.TABLE_PREFIX.'service_day` ORDER BY `sort` ';
            $this->data['visit_days'] = $this->db->readSqlList($sql); 

            $sql = 'SELECT C.`contract_id`,C.`type_id`,C.`client_code`,CL.`name` AS `client`,C.`price`,C.`price_visit`,C.`discount`,'.
                          'C.`no_assistants`,C.`pay_method_id`,C.`status`, '.
                          '(SELECT I.`date` FROM `'.$table_invoice.'` AS I WHERE I.`contract_id` = C.`contract_id` ORDER BY I.`date` DESC LIMIT 1) AS `date_last_invoice` '.
                   'FROM `'.$table_contract.'` AS C LEFT JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) '.
                   'WHERE C.`division_id` = "'.$this->db->escapeSql($division_id).'" AND C.`status` <> "HIDE" AND '.
                         'C.`round_id` = "'.$this->db->escapeSql($round_id).'" AND C.`type_id` = "'.$this->db->escapeSql($type_id).'" ';
            if($client_name !== '') $sql .= 'AND CL.`name` LIKE "%'.$this->db->escapeSql($client_name).'%" ';     
            if($client_code !== '') $sql .= 'AND C.`client_code` LIKE "%'.$this->db->escapeSql($client_code).'%" ';     
            $sql .='HAVING (`date_last_invoice` IS NULL OR `date_last_invoice` < "'.$this->db->escapeSql($date_last_invoice).'") '.
                   'ORDER BY C.`date_start` ';
            $contracts = $this->db->readSqlArray($sql); 
            if($contracts == 0) {
                $str = 'No contracts found without an invoice since '.$date_last_invoice;
                if($client_name !== '') $str .= '& client name contains text "'.$this->db->escapeSql($client_name).'" ';     
                if($client_code !== '')  $str .= '& client contract code contains text "'.$this->db->escapeSql($client_code).'" ';   
                $this->addError($str);
            } else {
                //assign contract pricing to invoice
                foreach($contracts as $id => $contract) {
                    $contract['inv_create'] = false;

                    $items = Helpers::getInvoiceItems($this->db,TABLE_PREFIX,$id,'ARRAY',$invoice_type);
                    $totals = $items['totals'];

                    $contract['inv_subtotal'] = number_format($totals['subtotal'],2);
                    $contract['inv_discount'] = number_format($totals['discount'],2);
                    $contract['inv_tax'] = number_format($totals['tax'],2);
                    $contract['inv_total'] = number_format($totals['total'],2);

                    $contract['inv_date'] = Helpers::getInvoiceDate($this->db,TABLE_PREFIX,$id);

                    //NB: == '' will capture null return but not ===            
                    if($contract['inv_date_last'] == '') {
                        $contract['inv_date_last'] = 'No invoices yet';
                    } else {
                        $contract['inv_date_last'] = Date::formatDateTime($contract['date_last_invoice']);
                    }    
                    
                    $contracts[$id] = $contract;
                }

                $this->data['contracts'] = $contracts;
            }     
        } 
        
        //process invoices
        if($this->page_no == 2) {
            $create_no = 0;
            
            foreach($this->data['contracts'] as $id => $contract) {
                $name_note = 'note_'.$id;
                $name_create = 'create_'.$id;
                $name_action = 'action_'.$id;
                $name_date = 'date_'.$id;
                       
                $contract['inv_note'] = Secure::clean('text',$_POST[$name_note]);
                $contract['inv_action'] = Secure::clean('basic',$_POST[$name_action]);
                $contract['inv_date'] = Secure::clean('date',$_POST[$name_date]);
                
                if(isset($_POST[$name_create]) and $_POST[$name_create] === 'YES') {
                    $contract['inv_create'] = true;
                    $create_no++;
                } else {
                    $contract['inv_create'] = false;
                }
                
                //replace contract for redisplay if errors and saving if no errors
                $this->data['contracts'][$id] = $contract;
            }

            if($create_no === 0) {
                $this->addError('You have not checked "Process invoice" for any contracts.');
            } else {
                $this->data['create_no'] = $create_no;
            }

            //finally process invoices
            if(!$this->errors_found) {
                foreach($this->data['contracts'] as $id => $contract) {
                    if($contract['inv_create']) {
                        $invoice_id = Helpers::saveInvoice($this->db,$this->table_prefix,$id,$contract['inv_note'],$contract['inv_date'],$error_tmp,$this->data['invoice_type']);   
                        if($error_tmp !== '') {
                            $contract['inv_message'] = $error_tmp;
                        } else {
                            Helpers::createInvoicePdf($this->db,$this->container,$invoice_id,$doc_name,$error_tmp);
                            if($error_tmp !== '') {
                                $contract['inv_message'] = $error_temp;
                            } else {
                                $contract['inv_message'] = 'Successfuly created invoice record and PDF document.';
                           
                                if($contract['inv_action'] === 'EMAIL') {
                                    $email_address = 'DEFAULT';
                                    Helpers::sendInvoice($this->db,$this->container,$invoice_id,$email_address,$error_tmp);
                                    if($error_tmp !== '') $contract['inv_message'] = 'Could not email invoice to client';
                                }
                            }    
                        }

                        $contracts[$id] = $contract;
                    }
                    

                }

                $this->data['contracts'] = $contracts;
            }    
        }
            
         //final page so no fucking processing possible moron
        if($this->page_no == 3) {
            
        } 
    }

    //public function setupPageData($no) {} 

}

?>


