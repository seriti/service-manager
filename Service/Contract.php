<?php
namespace App\Service;

use Seriti\Tools\TABLE_USER;
use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Contract extends Table
{
    public function setup($param = []) 
    {
        if(!isset($param['type'])) $param['type'] = 'REPEAT';

        if($param['type'] === 'REPEAT') {
            $row_name = 'Repeat contract';
        }

        if($param['type'] === 'SINGLE') {
            $row_name = 'Single contract';
        }

        $parent_param = ['row_name'=>$row_name,'col_label'=>'client_code'];
        parent::setup($parent_param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'contract_item','col_id'=>'contract_id','message'=>'Contract items exist for this Contract']);
        $this->addForeignKey(['table'=>TABLE_PREFIX.'contract_visit','col_id'=>'contract_id','message'=>'Service visits exist for this Contract']);

        //NB: sets contraxt type depending on controller param
        $this->addColFixed(['id'=>'type_id','value'=>$param['type']]);

        $this->addTableCol(['id'=>'contract_id','type'=>'INTEGER','title'=>'contract ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'division_id','type'=>'INTEGER','title'=>'Division','join'=>'name FROM '.TABLE_PREFIX.'division WHERE division_id']);
        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'Client','onchange'=>'clientChange()']);
        $this->addTableCol(['id'=>'client_code','type'=>'STRING','title'=>'Contract code','hint'=>'A unique code used to identify contract to client']);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location','join'=>'name FROM '.TABLE_PREFIX.'client_location WHERE location_id']);
        $this->addTableCol(['id'=>'contact_id','type'=>'INTEGER','title'=>'Contact','join'=>'name FROM '.TABLE_PREFIX.'client_contact WHERE contact_id']);
        
        
        $this->addTableCol(['id'=>'agent_id','type'=>'INTEGER','title'=>'Agent','join'=>'name FROM '.TABLE_PREFIX.'agent WHERE agent_id']);
        $this->addTableCol(['id'=>'user_id_responsible','type'=>'INTEGER','title'=>'User responsible','join'=>'name FROM '.TABLE_USER.' WHERE user_id','list'=>false]);
        $this->addTableCol(['id'=>'user_id_sold','type'=>'INTEGER','title'=>'User sold','join'=>'name FROM '.TABLE_USER.' WHERE user_id','list'=>false]);
        $this->addTableCol(['id'=>'user_id_signed','type'=>'INTEGER','title'=>'User signed','join'=>'name FROM '.TABLE_USER.' WHERE user_id','list'=>false]);
        $this->addTableCol(['id'=>'user_id_checked','type'=>'INTEGER','title'=>'User checked','join'=>'name FROM '.TABLE_USER.' WHERE user_id','list'=>false]);
        $this->addTableCol(['id'=>'signed_by','type'=>'STRING','title'=>'Signed by','hint'=>'Name of client representative who signed contract']);
        $this->addTableCol(['id'=>'date_signed','type'=>'DATE','title'=>'Date signed','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'date_start','type'=>'DATETIME','title'=>'Date start']);
                

        if($param['type'] === 'REPEAT') {
            $this->addTableCol(['id'=>'date_renew','type'=>'DATE','title'=>'Date renew','new'=>date('Y-m-d')]);
            $this->addTableCol(['id'=>'no_months','type'=>'INTEGER','title'=>'Contract months','new'=>12]);
            $this->addTableCol(['id'=>'no_visits','type'=>'INTEGER','title'=>'No visits','new'=>0]);
            $this->addTableCol(['id'=>'visit_day_id','type'=>'INTEGER','title'=>'Preferred visit day','join'=>'name FROM '.TABLE_PREFIX.'service_day WHERE day_id']);
            $this->addTableCol(['id'=>'visit_time_from','type'=>'TIME','title'=>'Preferred visit time from','new'=>'10:00']);
            $this->addTableCol(['id'=>'visit_time_to','type'=>'TIME','title'=>'Preferred visit time to','new'=>'12:00']);
            $this->addTableCol(['id'=>'time_estimate','type'=>'INTEGER','title'=>'Time estimate(minutes)']);
            $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price initial visit']);
            $this->addTableCol(['id'=>'price_visit','type'=>'DECIMAL','title'=>'Price per visit']); 
            $this->addTableCol(['id'=>'price_annual_pct','type'=>'DECIMAL','title'=>'Price annual pct']);  

            $search = ['contract_id','division_id','client_id','client_code','contact_id','agent_id','location_id',
                       'user_id_responsible','user_id_sold','user_id_signed','user_id_checked','signed_by',
                       'date_signed','date_renew','no_months','pay_method_id',
                       'visit_day','price','price_annual_pct','notes_admin','notes_client','status'];
        }

        if($param['type'] === 'SINGLE') {
            $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price']);
            $this->addTableCol(['id'=>'discount','type'=>'DECIMAL','title'=>'Discount','hint'=>'value less than 50 assumed to be percentage discount']);
            $this->addTableCol(['id'=>'time_estimate','type'=>'INTEGER','title'=>'Time estimate(minutes)']);

            $search = ['contract_id','division_id','client_id','client_code','contact_id','agent_id','location_id',
                       'user_id_responsible','user_id_sold','user_id_signed','user_id_checked','signed_by',
                       'date_signed','pay_method_id',
                       'price','notes_admin','notes_client','status'];
        }

        $this->addTableCol(['id'=>'warranty_months','type'=>'INTEGER','title'=>'Warranty months','new'=>12]);
        $this->addTableCol(['id'=>'no_assistants','type'=>'INTEGER','title'=>'No assistants required','new'=>1]);
        $this->addTableCol(['id'=>'pay_method_id','type'=>'INTEGER','title'=>'Payment method','join'=>'name FROM '.TABLE_PREFIX.'pay_method WHERE pay_method_id']);
        $this->addTableCol(['id'=>'round_id','type'=>'INTEGER','title'=>'Service Round','join'=>'name FROM '.TABLE_PREFIX.'service_round WHERE round_id']);
        $this->addTableCol(['id'=>'notify_prior','type'=>'BOOLEAN','title'=>'Notify prior']);

        $this->addTableCol(['id'=>'notes_admin','type'=>'TEXT','title'=>'Admin notes','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'notes_client','type'=>'TEXT','title'=>'Client notes','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSql('WHERE','T.type_id = "'.$param['type'].'" ');

        $this->addSortOrder('T.contract_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);
        //popups
        $this->addAction(['type'=>'popup','text'=>'Items','url'=>'contract_item','mode'=>'view','width'=>600,'height'=>600]);
        $this->addAction(['type'=>'popup','text'=>'Visits','url'=>'contract_visit','mode'=>'view','width'=>600,'height'=>600]);

        
        $search_rows = 4;           
        $this->addSearch($search,['rows'=>$search_rows]);

        
        $this->addSelect('division_id','SELECT division_id, name FROM '.TABLE_PREFIX.'division ORDER BY sort');
        $this->addSelect('client_id','SELECT client_id, name FROM '.TABLE_PREFIX.'client ORDER BY name');
        $this->addSelect('location_id','SELECT location_id, name FROM '.TABLE_PREFIX.'client_location ORDER BY sort');
        $this->addSelect('agent_id','SELECT agent_id, name FROM '.TABLE_PREFIX.'agent ORDER BY sort');
        $this->addSelect('pay_method_id','SELECT method_id, name FROM '.TABLE_PREFIX.'pay_method ORDER BY sort');

        $this->addSelect('contact_id','SELECT contact_id, name FROM '.TABLE_PREFIX.'client_contact ORDER BY name');
        $this->addSelect('round_id','SELECT round_id, name FROM '.TABLE_PREFIX.'service_round ORDER BY sort');
        $this->addSelect('visit_day_id','SELECT day_id, name FROM '.TABLE_PREFIX.'service_day ORDER BY sort');

        $sql = 'SELECT '.$this->user_cols['id'].','.$this->user_cols['name'].' FROM '.TABLE_USER.' WHERE zone <> "PUBLIC" ORDER BY '.$this->user_cols['name'];
        $this->addSelect('user_id_responsible',$sql);
        $this->addSelect('user_id_sold',$sql);
        $this->addSelect('user_id_signed',$sql);
        $this->addSelect('user_id_checked',$sql);
        
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'CON','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'contract_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function modifyRowValue($col_id,$data,&$value)
    {
        if($col_id === 'client_id') {
            $client = Helpers::get($this->db,TABLE_PREFIX,'client',$value);
            $value = $client['name'];
            if($client['email'] !== '') $value .= '<br/><a href="mailto:'.$client['email'].'">'.$client['email'].'</a>';
            if($client['tel'] !== '') $value .= '<br/>Tel: '.$client['tel'];

        }

        if($col_id === 'discount') {
           if($value <= 50) $value = $value.'%'; 
        }
    }
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

    public function getJavascript()
    {
        $js = "
        <script type='text/javascript'>
        $(document).ready(function() {
            if(form = document.getElementById('update_form')) {
                clientChange();
            }
        });

        function clientChange() {
            var form = document.getElementById('update_form');
            var client_id = form.client_id.value;
            var location_id = form.location_id.value;
            var contact_id = form.contact_id.value;
                      
            var param = 'client_id='+client_id;
            //alert('PARAM:'+param);
            xhr('ajax?mode=client_locations',param,showLocationList,location_id);
            xhr('ajax?mode=client_contacts',param,showContactList,contact_id);
              
        } 

        function showLocationList(str,location_id) {
            //alert(str);
            if(str.substring(0,5) === 'ERROR') {
                alert(str);
            } else {  
                var links = $.parseJSON(str);
                var sel = '';
                //use jquery to reset cols select list
                $('#location_id option').remove();
                $.each(links, function(i,item){
                    // Create and append the new options into the select list
                    if(i == location_id) sel = 'SELECTED'; else sel = '';
                    $('#location_id').append('<option value='+i+' '+sel+'>'+item+'</option>');
                });
            }    
        }

        function showContactList(str,contact_id) {
            //alert(str);
            if(str.substring(0,5) === 'ERROR') {
                alert(str);
            } else {  
                var links = $.parseJSON(str);
                var sel = '';
                //use jquery to reset cols select list
                $('#contact_id option').remove();
                $.each(links, function(i,item){
                    // Create and append the new options into the select list
                    if(i == contact_id) sel = 'SELECTED'; else sel = '';
                    $('#contact_id').append('<option value='+i+' '+sel+'>'+item+'</option>');
                });
            }    
        }
        </script>";

        return $js;

    }

}
