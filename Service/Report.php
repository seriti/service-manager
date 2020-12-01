<?php
namespace App\Service;

use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

use App\Service\Helpers;

class Report extends ReportTool
{
     

    //configure
    public function setup() 
    {
        //$this->report_header = 'WTF';
        $this->report_select_title = 'Select Report:';
        $this->always_list_reports = true;

        $param = ['input'=>['select_task']];
        $this->addReport('TASK_SUMMARY','Management task summary',$param); 
        
        
        //$this->addInput('select_provider','Select service provider');
        $this->addInput('select_task','Select management task');
        //$this->addInput('select_date_from','From date:'); 
        //$this->addInput('select_date_to','To date:'); 
        //$this->addInput('select_format',''); 
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        
        if($id === 'select_provider') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            $param['xtra'] = ['ALL'=>'All service providers'];
            $sql = 'SELECT provider_id,name FROM '.TABLE_PREFIX.'provider ORDER BY name'; 
            if(isset($form['provider_id'])) $provider_id = $form['provider_id']; else $provider_id = 'ALL';
            $html .= Form::sqlList($sql,$this->db,'provider_id',$provider_id,$param);
        }
        

        
        if($id === 'select_task') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            $sql = 'SELECT task_id,CONCAT(task_id,":",name) AS name FROM '.TABLE_PREFIX.'task ORDER BY date_create DESC'; 
            if(isset($form['task_id'])) $task_id = $form['task_id']; else $task_id = '';
            $html .= Form::sqlList($sql,$this->db,'task_id',$task_id,$param);
        }
        
        /*
        if($id === 'select_date_from') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_from'])) $date_from = $form['date_from']; else $date_from = date('Y-m-d',mktime(0,0,0,date('m')-12,date('j'),date('Y')));
            $html .= Form::textInput('date_from',$date_from,$param);
        }

        if($id === 'select_date_to') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_to'])) $date_to = $form['date_to']; else $date_to = date('Y-m-d');
            $html .= Form::textInput('date_to',$date_to,$param);
        }      
        
        if($id === 'select_format') {
            if(isset($form['format'])) $format = $form['format']; else $format = 'HTML';
            $html .= Form::radiobutton('format','PDF',$format).'&nbsp;<img src="/images/pdf_icon.gif">&nbsp;PDF document<br/>';
            $html .= Form::radiobutton('format','CSV',$format).'&nbsp;<img src="/images/excel_icon.gif">&nbsp;CSV/Excel document<br/>';
            $html .= Form::radiobutton('format','HTML',$format).'&nbsp;Show on page<br/>';
        }
        */

        return $html;       
    }

    protected function processReport($id,$form = []) 
    {
        $html = '';
        $error = '';
        $options = [];
        $options['format'] = $form['format'];
        
        if($id === 'TASK_SUMMARY') {
            $s3 = $this->container['s3'];
            //currently only html format supported
            $options['format'] = 'HTML';
            $html .= Helpers::taskReport($this->db,$s3,$form['task_id'],$options,$error);
            if($error !== '') $this->addError($error);
        }
        
        return $html;
    }

}
