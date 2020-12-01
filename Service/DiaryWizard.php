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


class DiaryWizard extends Wizard 
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
        $this->addVariable(array('id'=>'date_last_visit','type'=>'DATE','title'=>'Date last visited','required'=>true,'new'=>$date_last_visit));

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
        $this->addPage(1,'Setup','service/diary_page1.php',['go_back'=>true]);
        $this->addPage(2,'New contract visits required','service/diary_page2.php');
        $this->addPage(3,'Confirm details','service/diary_page3.php');
        $this->addPage(4,'Summary','service/diary_page4.php',['final'=>true]);
            

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //get contract list for processing
        if($this->page_no == 1) {
            $today = new \DateTime();
            
            $division_id = $this->form['division_id'];
            $type_id = $this->form['type_id'];
            $round_id = $this->form['round_id'];
            $date_last_visit = $this->form['date_last_visit'];

            $table_contract = TABLE_PREFIX.'contract';
            $table_visit = TABLE_PREFIX.'contract_visit';
            
            $this->data['division'] = Helpers::get($this->db,TABLE_PREFIX,'division',$division_id);
            $this->data['round'] = Helpers::get($this->db,TABLE_PREFIX,'service_round',$round_id,'round_id');

            //NB: expecting 1:monday/2:tuesday....etc
            $sql = 'SELECT day_id,LOWER(name) FROM '.TABLE_PREFIX.'service_day ORDER BY sort ';
            $this->data['visit_days'] = $this->db->readSqlList($sql); 

            $sql = 'SELECT C.contract_id,C.type_id,C.client_code,C.visit_day_id,C.visit_time_from,C.visit_time_to,C.time_estimate,C.date_start,C.no_assistants, '.
                          '(SELECT V.date_visit FROM '.$table_visit.' AS V WHERE V.contract_id = C.contract_id ORDER BY V.date_visit DESC LIMIT 1) AS date_last_visit '.
                   'FROM '.$table_contract.' AS C '.
                   'WHERE C.division_id = "'.$this->db->escapeSql($division_id).'" AND C.round_id = "'.$this->db->escapeSql($round_id).'" AND C.type_id = "'.$this->db->escapeSql($type_id).'" '.
                   'HAVING (date_last_visit IS NULL OR date_last_visit < "'.$this->db->escapeSql($date_last_visit).'") '.
                   'ORDER BY C.division_id,C.date_start ';
            $contracts = $this->db->readSqlArray($sql); 
            if($contracts == 0) {
                $this->addError('No contracts found without a visit since '.$date_last_visit);
            } else {
                //calculate best guesses of next visit
                foreach($contracts as $id => $contract) {
                    $contract['new_create'] = false;

                    if(!isset($contract['new_date'])) {//assign based on contract for first pass only
                        if($contract['type_id'] === 'SINGLE') {
                            $contract['new_date'] = substr($contract['date_start'],0,10);
                            $contract['new_time'] = substr($contract['date_start'],11,5);
                        } else {
                            //nb: expects 'next monday', or 'next tuesday'...etc
                            $str_mod = 'next '.$this->data['visit_days'][$contract['visit_day_id']];
                            $today->modify($str_mod);
                            $contract['new_date'] = $today->format('Y-m-d');
                            $contract['new_time'] = Date::formatTime($contract['visit_time_from']);
                        }
                        
                        $contract['new_minutes'] = $contract['time_estimate'];  
                        $contract['no_assist'] = $contract['no_assistants'];    
                    }
                                 
                    if($contract['date_last'] == '') {
                        $contract['date_last'] = 'No visits yet';
                    } else {
                        $contract['date_last'] = Date::formatDateTime($contract['date_last_visit']);
                    }    
                    
                    $contracts[$id] = $contract;
                }

                $this->data['contracts'] = $contracts;
            }     
        } 
        
        //process visits
        if($this->page_no == 2) {
            $create_no = 0;

            foreach($this->data['contracts'] as $id => $contract) {
                $name_cat = 'cat_'.$id;
                $name_date = 'date_'.$id;
                $name_time = 'time_'.$id;
                $name_minutes = 'minutes_'.$id;
                $name_assist = 'assist_'.$id;
                $name_create = 'create_'.$id;

                $contract['new_cat'] = Secure::clean('integer',$_POST[$name_cat]);
                $contract['new_date'] = Secure::clean('date',$_POST[$name_date]);
                $contract['new_time'] = Secure::clean('date',$_POST[$name_time]);
                $contract['new_minutes'] = Secure::clean('integer',$_POST[$name_minutes]);
                $contract['no_assist'] = Secure::clean('integer',$_POST[$name_assist]);
                if(isset($_POST[$name_create]) and $_POST[$name_create] === 'YES') {
                    $contract['new_create'] = true;
                } else {
                    $contract['new_create'] = false;
                }
                
                //replace contract for redisplay if errors and saving if no errors
                $this->data['contracts'][$id] = $contract;

                if($contract['new_create']) {
                    $error_entry = '';
                    $create_no++;

                    Validate::date('Visit date',$contract['new_date'],'YYYY-MM-DD',$error_tmp);
                    if($error_tmp !== '') {
                        $error_entry .= $error_tmp.' ';
                    } else {
                        if(Date::mysqlGetTime($contract['new_date']) < time()) $error_entry .= 'Next visit date cannot be today or earlier. ';
                    }    

                    Validate::time('Visit time start',$contract['new_time'],'HH:MM',$error_tmp);
                    if($error_tmp !== '') $error_entry .= $error_tmp.' ';
                    
                    Validate::integer('Visit estimated minutes',5,480,$contract['new_minutes'],$error_tmp);
                    if($error_tmp !== '') $error_entry .= $error_tmp.' ';

                    Validate::integer('Number of assistants',0,10,$contract['no_assist'],$error_tmp);
                    if($error_tmp !== '') $error_entry .= $error_tmp.' ';

                    if($error_entry !== '') {
                        $this->addError('Contract['.$id.'] code['.$contract['client_code'].'] Error: '.$error_entry);
                    } 
                    
                }

            }

            if($create_no === 0) {
                $this->addError('You have not checked "Create visit entry" for any contracts.');
            } else {
                $this->data['create_no'] = $create_no;
            }
        }
            
        
        //save order, email supplier
        if($this->page_no == 3) {
            $table_visit = $this->table_prefix.'contract_visit';
             
            $this->db->executeSql('START TRANSACTION',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not START transaction');

            if(!$this->errors_found) {
                foreach($this->data['contracts'] as $id => $contract) {
                    if($contract['new_create']) {
                        $visit = [];
                        $visit['contract_id'] = $id;
                        $visit['round_id'] = $this->form['round_id'];
                        $visit['category_id'] = $contract['new_cat'];
                        $visit['date_visit'] = $contract['new_date'];
                        $visit['time_from'] = $contract['new_time'];
                        $visit['time_to'] = Date::incrementTime($contract['new_time'],$contract['new_minutes']);
                        $visit['no_assistants'] = $contract['no_assist'];
                        $visit['status'] = 'NEW';

                        $visit_id = $this->db->insertRecord($table_visit,$visit,$error_tmp);
                        if($error_tmp !== '') {
                            $error = 'Could not create diary visit for contract ID['.$id.'] ';
                            if($this->debug) $error .= $error_tmp;
                            $this->addError($error);
                        } 
                    }
                }
            }

            if($this->errors_found) {
                $this->db->executeSql('ROLLBACK',$error_tmp);
                if($error_tmp !== '') $this->addError('Could not ROLLBACK transaction');
            } else {
                $this->db->executeSql('COMMIT',$error_tmp);
                if($error_tmp !== '') $this->addError('Could not COMMIT transaction');
            }
        } 

        //final page so no fucking processing possible moron
        if($this->page_no == 4) {

            

            

            
        } 
    }

    //public function setupPageData($no) {} 

}

?>


