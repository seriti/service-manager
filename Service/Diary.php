<?php
namespace App\Service;

use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

use App\Service\Helpers;

class Diary extends ReportTool
{
    protected $status_arr = ['ALL'=>'ALL entries','NEW'=>'Preliminary entries only','CONFIRMED'=>'Confirmed entries only','COMPLETED'=>'Completed visits only'];
    protected $time_arr = ['00:00'=>'0am','01:00'=>'1am','02:00'=>'2am','03:00'=>'3am','04:00'=>'4am','05:00'=>'5am','06:00'=>'6am','07:00'=>'7am','08:00'=>'8am',
                           '09:00'=>'9am','10:00'=>'10am','11:00'=>'11am','12:00'=>'12am','13:00'=>'1pm','14:00'=>'2pm','15:00'=>'3pm','16:00'=>'4pm','17:00'=>'5pm',
                           '18:00'=>'6pm','19:00'=>'7pm','20:00'=>'8pm','21:00'=>'9pm','22:00'=>'10pm','23:00'=>'11pm','24:00'=>'12pm'];

    //configure
    public function setup() 
    {
        //$this->report_header = '';
        $param = [];
        $this->report_select_title = '';
        $this->always_list_reports = true;
        $this->submit_title = 'View Diary';

        $param = ['input'=>['select_round','select_technician','select_date_period','select_time_period','select_status']];
        $this->addReport('DIARY_DAYS','Daily round diary',$param); 
       
        
        
        $this->addInput('select_round','');
        $this->addInput('select_technician','');
        $this->addInput('select_date_period','');
        $this->addInput('select_time_period','');
        $this->addInput('select_month_period','');
        $this->addInput('select_status','');
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        
        if($id === 'select_round') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            $param['xtra'] = ['ALL'=>'All Rounds'];
            $sql = 'SELECT `round_id`,`name` FROM `'.TABLE_PREFIX.'service_round` WHERE `status` <> "HIDE" ORDER BY `sort`'; 
            if(isset($form['round_id'])) $round_id = $form['round_id']; else $round_id = '';
            $html .= 'Round:&nbsp;'.Form::sqlList($sql,$this->db,'round_id',$round_id,$param);
        }

        if($id === 'select_technician') {
            $param = [];
            $param['class'] = 'form-control input-medium input-inline';
            $param['xtra'] = ['ALL'=>'All technicians'];
            $sql = 'SELECT `user_id`,`name` FROM `'.TABLE_USER.'` WHERE `zone` <> "PUBLIC" AND `status` <> "HIDE" ORDER BY `name`'; 
            if(isset($form['user_id_tech'])) $user_id_tech = $form['user_id_tech']; else $user_id_tech = 'ALL';
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

        if($id === 'select_date_period') {
            $param = [];
            $param['class'] = $this->classes['date'].' input-inline';
            if(isset($form['date_from'])) $date_from = $form['date_from']; else $date_from = date('Y-m-d');
            $html .= 'Date from:&nbsp;'.Form::textInput('date_from',$date_from,$param);
       
            $param = [];
            $param['class'] = $this->classes['date'].' input-inline';
            if(isset($form['date_to'])) $date_to = $form['date_to']; else $date_to = date('Y-m-d',mktime(0,0,0,date('m'),date('j')+7,date('Y')));
            $html .= 'To:&nbsp;'.Form::textInput('date_to',$date_to,$param);
        }   

        if($id === 'select_time_period') {
            $param = [];
            $param['class'] = 'form-control input-small input-inline';
            $key_assoc = true;
            if(isset($form['time_from'])) $time_from = $form['time_from']; else $time_from = DIARY_SETUP['from_time']; 
            $html .= 'Time from:&nbsp;'.Form::arrayList($this->time_arr,'time_from',$time_from,$key_assoc,$param);
                   
            if(isset($form['time_to'])) $time_to = $form['time_to']; else $time_to = DIARY_SETUP['to_time'];
            $html .= 'To:&nbsp;'.Form::arrayList($this->time_arr,'time_to',$time_to,$key_assoc,$param);
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
            if($form['round_id'] === 'ALL' and $form['user_id_tech'] === 'ALL') {
                $this->addError('You cannot view Diary for ALL rounds and ALL technicians. Either select a technician and ALL rounds, or select a round and ALL technicians');
            }

            $minutes = Date::calcMinutes($form['time_from'],$form['time_to']);
            if($minutes < 60) {
                $this->addError('Time to['.$this->time_arr[$form['time_to']].'] must be at least 1 hour after time from['.$this->time_arr[$form['time_from']].'].');
            }

            if(!$this->errors_found) {
                $options['time_from'] = $form['time_from'];
                $options['time_to'] = $form['time_to'];
                $options['interval'] = DIARY_SETUP['interval'];

                $html = Helpers::roundDailyDiary($this->db,TABLE_PREFIX,$form['round_id'],$form['user_id_tech'],$form['status'],$form['date_from'],$form['date_to'],$options,$error);
                //NB: error should not stop display of calendar
                if($error !== '') $this->addMessage($error);

                if($form['round_id'] === 'ALL') {
                    $round_str = '<strong>ALL</strong> rounds';
                    $link_str = '';
                } else {
                    $round = Helpers::get($this->db,TABLE_PREFIX,'service_round',$form['round_id'],'round_id'); 
                    $round_str = '<strong>'.$round['name'].'</strong> round';
                    $href = "javascript:open_popup('diary_visit?mode=new&round_id=".$form['round_id']."',400,600)";
                    $link_str = '<a href="'.$href.'"><input type="button" value="Add an entry" class="'.$this->classes['button'].'"></a>';
                }
                
                if($form['user_id_tech'] === 'ALL') {
                    $tech_str = 'for <strong>All</strong> technicians';
                } else {
                    $user = $this->container->user->getUser('ID',$form['user_id_tech']);
                    $tech_str = 'for technician <strong>'.$user['name'].'</strong>';
                }
                
                $title = '<h2>'.$link_str.' '.$this->status_arr[$form['status']].' on '.$round_str.' '.
                         'from <strong>'.Date::formatDate($form['date_from']).'</strong> to <strong>'.Date::formatDate($form['date_to']).'</strong> '.$tech_str;
                         '</h2>';

                
                $html = $title.$html;
            }    
        }


        

        return $html;
    }

}

?>