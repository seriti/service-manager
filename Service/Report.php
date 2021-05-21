<?php
namespace App\Service;

use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

use App\Service\Helpers;
use App\Service\HelpersReport;

class Report extends ReportTool
{
    protected $visit_status = ['ALL'=>'ALL entries','NEW'=>'Preliminary entries only','CONFIRMED'=>'Confirmed entries only','COMPLETED'=>'Completed visits only'];

    //configure
    public function setup() 
    {
        //$this->report_header = 'WTF';
        $this->report_select_title = 'Select Report:';
        $this->report_select_class = 'form-control input-large';
        $this->always_list_reports = true;
        $this->submit_title = 'View Report';

        $param = ['input'=>['select_round','select_date','select_technician']];
        $this->addReport('TECH_WORKSHEET','Technician daily round worksheet PDF',$param); 

        $param = ['input'=>['select_division','select_date_period','select_format']];
        $this->addReport('INVOICE_EXPORT','Division Invoice Pastel-CSV export',$param); 

        $param = ['input'=>['select_division','select_date_period','select_format']];
        $this->addReport('INVOICE_SUMMARY','Division Invoices issued',$param); 

        $param = ['input'=>['select_division','select_format']];
        $this->addReport('CONTRACT_ORPHAN_INVOICE','Contracts without an invoice',$param);
        $this->addReport('CONTRACT_ORPHAN_VISIT','Contracts without a planned or completed visit',$param); 
        
        $this->addInput('select_division','');
        //$this->addInput('select_contract_type','');
        $this->addInput('select_round','');
        $this->addInput('select_date_period','');
        $this->addInput('select_date','');
        $this->addInput('select_technician','');
        $this->addInput('select_format',''); 
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        
        if($id === 'select_division') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            $param['xtra'] = ['ALL'=>'All divisions'];
            $sql = 'SELECT division_id,name FROM '.TABLE_PREFIX.'division ORDER BY name'; 
            if(isset($form['division_id'])) $division_id = $form['division_id']; else $division_id = 'ALL';
            $html .= Form::sqlList($sql,$this->db,'division_id',$division_id,$param);
        }
                
        if($id === 'select_contract_type') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            if(isset($form['contract_type'])) $contract_type = $form['contract_type']; else $contract_type = 'REPEAT';
            $type_arr = ['SINGLE'=>'Single shot contracts','REPEAT'=>'Repeat contracts'];
            $html .= Form::arrayList($type_arr,'contract_type',$contract_type,true,$param); 
        }

        if($id === 'select_round') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            $param['xtra'] = ['ALL'=>'All rounds'];
            $sql = 'SELECT round_id,name FROM '.TABLE_PREFIX.'service_round WHERE status <> "HIDE" ORDER BY sort'; 
            if(isset($form['round_id'])) $round_id = $form['round_id']; else $round_id = '';
            $html .= 'Round:&nbsp;'.Form::sqlList($sql,$this->db,'round_id',$round_id,$param);
        }

         if($id === 'select_technician') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            $sql = 'SELECT user_id,name FROM '.TABLE_USER.' WHERE status <> "HIDE" ORDER BY name'; 
            if(isset($form['user_id_tech'])) $user_id_tech = $form['user_id_tech']; else $user_id_tech = '';
            $html .= 'Technician:&nbsp;'.Form::sqlList($sql,$this->db,'user_id_tech',$user_id_tech,$param);
        }
        
        if($id === 'select_month_period') {
            $past_years = 10;
            $future_years = 0;

            $param = [];
            $param['class'] = 'form-control input-small input-inline';
            
            $html .= 'From:';
            if(isset($form['from_month'])) $from_month = $form['from_month']; else $from_month = 1;
            if(isset($form['from_year'])) $from_year = $form['from_year']; else $from_year = date('Y');
            $html .= Form::monthsList($from_month,'from_month',$param);
            $html .= Form::yearsList($from_year,$past_years,$future_years,'from_year',$param);
            $html .= '&nbsp;&nbsp;To:';
            if(isset($form['to_month'])) $to_month = $form['to_month']; else $to_month = date('m');
            if(isset($form['to_year'])) $to_year = $form['to_year']; else $to_year = date('Y');
            $html .= Form::monthsList($to_month,'to_month',$param);
            $html .= Form::yearsList($to_year,$past_years,$future_years,'to_year',$param);
        }

        if($id === 'select_date') {
            $param = [];
            $param['class'] = $this->classes['date'].' input-inline';
            if(isset($form['date'])) $date = $form['date']; else $date = date('Y-m-d');
            $html .= 'Date:&nbsp;'.Form::textInput('date',$date,$param);
        }  

        if($id === 'select_date_period') {
            $param = [];
            $param['class'] = $this->classes['date'].' input-inline';
            if(isset($form['date_from'])) $date_from = $form['date_from']; else $date_from = date('Y-m-d');
            $html .= 'From:&nbsp;'.Form::textInput('date_from',$date_from,$param);
       
            $param = [];
            $param['class'] = $this->classes['date'].' input-inline';
            if(isset($form['date_to'])) $date_to = $form['date_to']; else $date_to = date('Y-m-d',mktime(0,0,0,date('m'),date('j')+7,date('Y')));
            $html .= 'To:&nbsp;'.Form::textInput('date_to',$date_to,$param);
        }      
        
        if($id === 'select_visit_status') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            if(isset($form['visit_status'])) $visit_status = $form['visit_status']; else $visit_status = 'ALL';
            $key_assoc = true;
            $html .= 'Status: '.Form::arrayList($this->visit_status,'visit_status',$visit_status,$key_assoc,$param);
        }   
        
        if($id === 'select_format') {
            if(isset($form['format'])) $format = $form['format']; else $format = 'HTML';
            $html .= Form::radiobutton('format','PDF',$format).'&nbsp;<img src="/images/pdf_icon.gif">&nbsp;PDF document<br/>';
            $html .= Form::radiobutton('format','CSV',$format).'&nbsp;<img src="/images/excel_icon.gif">&nbsp;CSV/Excel document<br/>';
            $html .= Form::radiobutton('format','HTML',$format).'&nbsp;Show on page<br/>';
        }
        

        return $html;       
    }

    protected function processReport($id,$form = []) 
    {
        $html = '';
        $error = '';
        $options = [];
        $options['format'] = $form['format'];
        
        if($id === 'TECH_WORKSHEET') {
            $html = HelpersReport::dailyTechWorksheet($this->db,$form['round_id'],$form['date'],$form['user_id_tech'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'INVOICE_EXPORT') {
            $html = HelpersReport::invoiceCsvExport($this->db,$form['division_id'],$form['date_from'],$form['date_to'],$options,$error);
            if($error !== '') $this->addError($error);
        }

         if($id === 'INVOICE_SUMMARY') {
            $html = HelpersReport::invoiceSummary($this->db,$form['division_id'],$form['date_from'],$form['date_to'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'CONTRACT_ORPHAN_INVOICE' or $id === 'CONTRACT_ORPHAN_VISIT') {
            if($id === 'CONTRACT_ORPHAN_INVOICE') $type = 'INVOICE';
            if($id === 'CONTRACT_ORPHAN_VISIT') $type = 'VISIT';
            $html = HelpersReport::contractOrphan($this->db,$type,$form['division_id'],$options,$error);
            if($error !== '') $this->addError($error);
        }
        
        return $html;
    }

}
