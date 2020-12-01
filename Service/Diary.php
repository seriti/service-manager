<?php
namespace App\Service;

use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

use App\Service\Helpers;

class Diary extends ReportTool
{
    protected $status_arr = ['ALL'=>'ALL entries','NEW'=>'Preliminary entries only','CONFIRMED'=>'Confirmed entries only','COMPLETED'=>'Completed visits only'];

    //configure
    public function setup() 
    {
        //$this->report_header = '';
        $param = [];
        $this->report_select_title = '';
        $this->always_list_reports = false;
        $this->submit_title = 'View Diary';

        $param = ['input'=>['select_round','select_date_period','select_status']];
        $this->addReport('DIARY_DAYS','Daily round diary',$param); 
       
        
        
        $this->addInput('select_round','');
        $this->addInput('select_date_period','');
        $this->addInput('select_month_period','');
        $this->addInput('select_status','');
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        
        if($id === 'select_round') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            //$param['xtra'] = ['ALL'=>'All locations'];
            $sql = 'SELECT round_id,name FROM '.TABLE_PREFIX.'service_round WHERE status <> "HIDE" ORDER BY sort'; 
            if(isset($form['round_id'])) $round_id = $form['round_id']; else $round_id = '';
            $html .= 'Round:&nbsp;'.Form::sqlList($sql,$this->db,'round_id',$round_id,$param);
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
        
        if($id === 'select_status') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            if(isset($form['status'])) $status = $form['status']; else $status = 'ALL';
            $key_assoc = true;
            $html .= 'Status: '.Form::arrayList($this->status_arr,'status',$status,$key_assoc,$param);
        }
        
        return $html;       
    }

    protected function processReport($id,$form = []) 
    {
        $html = '';
        $error = '';
        $options = [];
        
        
        
        if($id === 'DIARY_DAYS') {
            $html = Helpers::roundDailyDiary($this->db,TABLE_PREFIX,$form['round_id'],$form['status'],$form['date_from'],$form['date_to'],$options,$error);
            //NB: error should not stop display of calendar
            if($error !== '') $this->addMessage($error);

            $round = Helpers::get($this->db,TABLE_PREFIX,'service_round',$form['round_id'],'round_id'); 
            $href = "javascript:open_popup('diary_visit?mode=new&round_id=".$form['round_id']."',400,600)";
            
            $title = '<h2><a href="'.$href.'"><input type="button" value="Add an entry" class="'.$this->classes['button'].'"></a> '.
                     $this->status_arr[$form['status']].' on <strong>'.$round['name'].'</strong> round from <strong>'.Date::formatDate($form['date_from']).'</strong> to <strong>'.Date::formatDate($form['date_to']).'</strong>'.
                     '</h2>';

            
            $html = $title.$html;
        }


        

        return $html;
    }

}

?>