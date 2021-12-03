<?php
namespace App\Service;

use Exception;
use Seriti\Tools\Calendar;
use Seriti\Tools\Calc;
use Seriti\Tools\Csv;
use Seriti\Tools\Doc;
use Seriti\Tools\Html;
use Seriti\Tools\Pdf;
use Seriti\Tools\Date;
use Seriti\Tools\Secure;
use Seriti\Tools\Upload;
use Seriti\Tools\SITE_TITLE;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\STORAGE;
use Seriti\Tools\SITE_NAME;
use Seriti\Tools\TABLE_USER;
use Seriti\Tools\AJAX_ROUTE;

use Psr\Container\ContainerInterface;

use App\Service\Helpers;


//static functions for service module
class HelpersReport {

    public static function visitFeedback($db,$division_id,$round_id,$status,$date_from,$date_to,$options = [],&$error)
    {
        $error = '';


        if(!isset($options['output'])) $options['output'] = 'BROWSER';
        if(!isset($options['format'])) $options['format'] = 'CSV';
        $options['format'] = strtoupper($options['format']);

        if(!isset($options['user_id_tech'])) $options['user_id_tech'] = 'ALL';
        
        if($division_id === 'ALL') {
            $division_name = 'all divisions';
        } else {
            $division = Helpers::get($db,TABLE_PREFIX,'division',$division_id);
            if($division == 0) {
                $error .= 'Invalid Division['.$division_id.'] selected.';
            } else {
                $division_name = $division['name'];
            }    
        }

        if($round_id === 'ALL') {
            $round_name = 'all rounds';
        } else {
            $round = Helpers::get($db,TABLE_PREFIX,'service_round',$round_id,'round_id');
            if($round == 0) {
                $error .= 'Invalid round['.$round_id.'] selected.';
            } else {
                $round_name = $round['name'];
            }    
        }
     
        
        if($error !== '') return false;

        $doc_name_base = str_replace(' ','_',$round_name).'_'.str_replace(' ','_',$division_name).'_'.strtolower($status).'_visits_from_'.
                         Date::formatDate($date_from).'_to_'.Date::formatDate($date_to).'_on_'.date('Y-m-d');

        $page_title = $round_name.' '.$division_name.' '.$status.' visits from '.Date::formatDate($date_from).' to '.Date::formatDate($date_to);

        $table_visit = TABLE_PREFIX.'contract_visit';
        $table_contract = TABLE_PREFIX.'contract';
        $table_client = TABLE_PREFIX.'client';
        $table_user = TABLE_USER;
        $table_round = TABLE_PREFIX.'service_round';
        $table_feedback = TABLE_PREFIX.'service_feedback';
                
        /*
        SELECT V.visit_id,V.contract_id,V.user_id_booked,V.user_id_tech,U.name AS technician, '.
                      'V.date_booked,V.date_visit,V.notes,V.status,V.time_from,V.time_to,V.status, '.
                      'C.client_code, C.client_id,CL.name AS client,R.name AS round
        */

        $sql = 'SELECT CL.`name` AS `client`,U.`name` AS `technician`, '.
                      'V.`date_visit`,V.`status`,F.`name` AS `feedback` '.
               'FROM `'.$table_visit.'` AS V '.
                     'JOIN `'.$table_contract.'` AS C ON(V.`contract_id` = C.`contract_id`) '.
                     'JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) '.
                     'LEFT JOIN `'.$table_user.'` AS U ON(V.`user_id_tech` = U.`user_id`) '.
                     'LEFT JOIN `'.$table_round.'` AS R ON(V.`round_id` = R.`round_id`) '.
                     'LEFT JOIN `'.$table_feedback.'` AS F ON(V.`feedback_id` = F.`feedback_id`) '.
               'WHERE V.`date_visit` >= "'.$db->escapeSql($date_from).'" AND V.`date_visit` <= "'.$db->escapeSql($date_to).'" ';
        if($division_id !== 'ALL') $sql .= 'AND C.`division_id` = "'.$db->escapeSql($division_id).'" ';    
        if($round_id !== 'ALL') $sql .= 'AND C.`round_id` = "'.$db->escapeSql($round_id).'" ';       
        if($options['user_id_tech'] != 'ALL') $sql .= 'AND V.`user_id_tech` = "'.$db->escapeSql($options['user_id_tech']).'" ';
        if($status != 'ALL') {
            if($status === 'FEEDBACK') {
                $sql .= 'AND F.`name` IS NOT NULL ';
            } else {
                $sql .= 'AND V.`status` = "'.$db->escapeSql($status).'" ';    
            }
        }   
        
        $sql .= 'ORDER BY V.`date_visit`, F.`sort` ';

        //$error .= $sql;

        $visits = $db->readSqlResult($sql,false);
        if($visits == 0) {
            $error .= 'No visits found for: '.$page_title;
        }

        if($error !== '') return false;

            

        $col_width = array(40,40,20,20,60);
        $col_type = array('','','DATE','','');

        if($options['format'] === 'PDF') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.pdf';
            
            $pdf = new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            
            $pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options = [];
            $pdf_options['font_size'] = 8;
            $row_h = 6;
            
            $pdf->mysqlDrawTable($visits,$row_h,$col_width,$col_type,'L',$pdf_options);

            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($doc_name,'D');    
            exit;
        }


        if($options['format'] === 'HTML') {
            $html_options = [];
            $html_options['col_type'] = $col_type;
            $html .= '<h2>'.$page_title.'</h2>';
            $html .= Html::mysqlDumpHtml($visits,$html_options);
        }

        if($options['format'] === 'CSV') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.csv';
            $csv_data = Csv::mysqlDumpCsv($visits);
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
        }               

        return $html;
    }
    
    public static function workPlanning($db,$mode,$division_id,$date_from,$date_to,$options,&$error)
    {
        $error = '';
        $table_prefix = TABLE_PREFIX;
        
        if(!isset($options['output'])) $options['output'] = 'BROWSER';
        if(!isset($options['format'])) $options['format'] = 'HTML';
        $options['format'] = strtoupper($options['format']);

        if(!isset($options['status'])) $options['status'] = 'ALL';
        if(!isset($options['type_id'])) $options['type_id'] = 'ALL';
        
        $date_last_visit = $date_form;
        $ignore_date_visit = true;

        
        $sql_where = 'C.`date_start` < "'.$db->escapeSql($date_to).'" ';

        if($options['type_id'] !== 'ALL') $sql_where .= 'AND C.`type_id` = "'.$db->escapeSql($options['type_id']).'" ';

        if($options['status'] !== 'ALL') $sql_where .= 'AND C.`status` = "'.$db->escapeSql($options['status']).'" ';

        if($division_id === 'ALL') {
            $base_doc_name = 'ALL_divisions';
            $page_title = 'All divisions';
        } else {
            $sql_where .= 'AND C.`division_id` = "'.$db->escapeSql($division_id).'" ';

            $division = Helpers::get($db,TABLE_PREFIX,'division',$division_id);
            if($division == 0) $error .= 'Invalid Division['.$division_id.'] selected.';

            $base_doc_name = str_replace(' ','_',$division['name']);
            $page_title = $division['name'];
        }  

        $base_doc_name .= '_'.$options['status'].'_Contracts_without_';
        $page_title .= ' '.$options['status'].' Contracts without ';     
        
        if($error !== '') return false;

        $table_visit = $table_prefix.'contract_visit';
        $table_invoice = $table_prefix.'contract_invoice';
        $table_category = $table_prefix.'visit_category';
        $table_contract = $table_prefix.'contract';
        $table_location = $table_prefix.'client_location';
        $table_client = $table_prefix.'client';
        $table_contact = $table_prefix.'client_contact';
        $table_round = $table_prefix.'service_round';
        $table_user = TABLE_USER;
             
        
        $sql = 'SELECT C.`contract_id`,C.`type_id`,C.`client_code`,CL.`name` AS `client`,C.`date_start`,C.`no_assistants`,C.`notes_admin`, '.
                      'R.`name` AS `round`, C.`price_visit`,C.`price_audit`,C.`no_visits`, '.
                      '(SELECT COUNT(*) FROM `'.$table_visit.'` AS V WHERE V.`contract_id` = C.`contract_id` AND V.`status` IN("COMPLETED","INVOICED")) AS `visit_count`,  '.
                      '(SELECT COUNT(*) FROM `'.$table_invoice.'` AS I WHERE I.`contract_id` = C.`contract_id`) AS invoice_count,  '.
                      '(SELECT V.`date_visit` FROM `'.$table_visit.'` AS V WHERE V.`contract_id` = C.`contract_id` AND V.`status` IN("COMPLETED","INVOICED") ORDER BY V.`date_visit` DESC LIMIT 1) AS `date_last_visit`, '.
                      '(SELECT DATE(I.`date`) FROM `'.$table_invoice.'` AS I WHERE I.`contract_id` = C.`contract_id` ORDER BY I.`date` DESC LIMIT 1) AS `date_last_invoice` '.
               'FROM `'.$table_contract.'` AS C LEFT JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) '.
                     'LEFT JOIN `'.$table_round.'` AS R ON (C.`round_id` = R.`round_id`) '.
               'WHERE '.$sql_where;
        if(!$ignore_date_visit) $sql .= 'HAVING (`date_last_visit` IS NULL OR `date_last_visit` < "'.$db->escapeSql($date_last_visit).'") ';  
        $sql .= 'ORDER BY R.`name`, C.`date_start` ';


        $contracts = $db->readSqlArray($sql,false);
        if($contracts == 0) $error .= 'No contracts found matching your criteria';
        
        if($error !== '') return false;

        $base_doc_name = str_replace(' ','_',$division['name']).'_division_work_due_from_'.
                         Date::formatDate($date_from).'_to_'.Date::formatDate($date_to).'_on_'.date('Y-m-d');

        $page_title = $division['name'].' work due from '.Date::formatDate($date_from).' to '.Date::formatDate($date_to);
        
        //block table parameters
        $col_width=array(50,30,20,10,20,20,10,20,10);
        $col_type=array('','','','','','DATE','','DATE','');

        $data = [];
        $r = 0;
        $data[0][$r] = 'Client';
        $data[1][$r] = 'Contract code';
        $data[2][$r] = 'Round';
        $data[3][$r] = 'Contract visits';
        $data[4][$r] = 'Price/visit';
        $data[5][$r] = 'Last visit';
        $data[6][$r] = 'Visits done';
        $data[7][$r] = 'Last Invoice';
        $data[8][$r] = 'Invoices issued';
                
        foreach($contracts as $contract) {
            $r ++;
            $data[0][$r] = $contract['client'];
            $data[1][$r] = $contract['client_code'];
            $data[2][$r] = $contract['round'];
            $data[3][$r] = $contract['no_visits'];
            $data[4][$r] = $contract['price_visit'];
            $data[5][$r] = $contract['date_last_visit'];
            $data[6][$r] = $contract['visit_count'];
            $data[7][$r] = $contract['date_last_invoice'];
            $data[8][$r] = $contract['invoice_count'];
        }
        
        if($options['format'] === 'PDF') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.pdf';
            
            $pdf = new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            
            $pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options = [];
            $pdf_options['font_size'] = 8;
            $row_h = 6;

            $pdf->changeFont('TEXT');
            $pdf->arrayDrawTable($data,$row_h,$col_width,$col_type,'L',$pdf_options);
            $pdf->Ln($row_h);
                                    
            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($doc_name,'D');    
            exit;
        }    

        if($options['format'] === 'HTML') {
            $html_options = [];
            $html_options['col_type'] = $col_type;
            $html = Html::arrayDumpHtml2($data,$html_options);

            return $html;
        }

        if($options['format'] === 'CSV') {
            $doc_name = $base_doc_name.'.csv';
            $csv_data = Csv::arrayDumpCsv($data);
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
        }  
    
    }


    public static function dailyTechWorksheet($db,$round_id,$date,$user_id_tech,$options = [],&$error)
    {
        $error = '';
        $table_prefix = TABLE_PREFIX;
        
        if(!isset($options['output'])) $options['output'] = 'BROWSER';
        
        /*
        if(!isset($options['format'])) $options['format'] = 'PDF';
        $options['format'] = strtoupper($options['format']);
        if($options['format'] !== 'PDF') {
            $error .= 'Format '.$options['format'].' NOT available.';
        } 
        */

        $table_visit = $table_prefix.'contract_visit';
        $table_category = $table_prefix.'visit_category';
        $table_contract = $table_prefix.'contract';
        $table_location = $table_prefix.'client_location';
        $table_client = $table_prefix.'client';
        $table_contact = $table_prefix.'client_contact';
        $table_round = $table_prefix.'service_round';
        $table_user = TABLE_USER;

        if($round_id !== 'ALL') {
            $round = helpers::get($db,$table_prefix,'service_round',$round_id,'round_id');
            if($round == 0) $error .= 'Invalid round ID['.$round_id.']';
        } else {
            $round['name'] = 'ALL';
        }
        
        $technician = helpers::get($db,'',$table_user,$user_id_tech,'user_id');
        if($technician == 0) $error .= 'Invalid Technician user ID['.$user_id_tech.']';
        
        //get all confirmed visits        
        $sql = 'SELECT V.`visit_id`,V.`contract_id`,V.`user_id_booked`,U.`name` AS `booked_by`,V.`category_id`,VC.`name` AS `category`, '.
                      'V.`date_booked`,V.`date_visit`,V.`notes`,V.`status`,V.`time_from`,V.`time_to`,V.`status`, '.
                      'C.`client_code`, C.`notes_admin`,C.`notes_client`,C.`client_id`,CL.`name` AS `client`,C.`location_id`,'.
                      'L.`name` AS `location`,L.`address`,  '.
                      'C.`contact_id`,CN.`name` AS `contact`,CN.`position` AS `contact_position`,CN.`tel`,CN.`tel_alt`,CN.`cell`,CN.`cell_alt`, '.
                      'R.`name` AS `round` '.
               'FROM `'.$table_visit.'` AS V '.
                     'LEFT JOIN `'.$table_category.'` AS VC ON(V.`category_id` = VC.`category_id`) '.   
                     'JOIN `'.$table_contract.'` AS C ON(V.`contract_id` = C.`contract_id`) '.
                     'JOIN `'.$table_location.'` AS L ON(C.`location_id` = L.`location_id`) '.
                     'JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) '.
                     'JOIN `'.$table_contact.'` AS CN ON(C.`contact_id` = CN.`contact_id`) '.
                     'LEFT JOIN `'.$table_user.'` AS U ON(V.`user_id_booked` = U.`user_id`) '.
                     'LEFT JOIN `'.$table_round.'` AS R ON(V.`round_id` = R.`round_id`) '.
               'WHERE V.`status` = "CONFIRMED" AND '.
                     'V.`date_visit` = "'.$db->escapeSql($date).'" AND '.
                     'V.`user_id_tech` = "'.$db->escapeSql($user_id_tech).'" ';
        if($round_id !== 'ALL') $sql .= 'AND V.`round_id` = "'.$db->escapeSql($round_id).'" ';
        $sql .= 'ORDER BY V.`time_from`';

        $visits = $db->readSqlArray($sql,false);
        if($visits == 0) $error .= 'No CONFIRMED diary visits found for technician on date '.$date;
        
        if($error !== '') return false;

        $base_doc_name = 'Worksheet_'.$round['name'].'_'.$date.'_'.$technician['name'];
        $page_title = 'Round: '.$round['name'].' for date '.Date::formatDate($date).' & technician: '.$technician['name'];
        
        //block table parameters
        $col_width=array(30,120,40);
        $col_type=array('','','');

        $visit_base = [];
        $r = 0;
        $visit_base[0][$r] = '';
        $visit_base[1][$r] = 'Planned';
        $visit_base[2][$r] = 'Notes/Updates';

        //create PDF
        if($error === '') {

            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.pdf';
            
            $pdf = new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            
            $pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options = [];
            $pdf_options['font_size'] = 8;
            $row_h = 6;
            $row_h2 = 5;

            $pdf->changeFont('H2');
            $pdf->Ln($row_h);
            $pdf->Cell(30,$row_h,'Assistants:',0,0,'R',0);
            $pdf->Cell(30,$row_h,'______________________',0,0,'L',0);
            $pdf->Cell(70,$row_h,'Vehicle:',0,0,'R',0);
            $pdf->Cell(70,$row_h,'______________________',0,0,'L',0);
            $pdf->Ln($row_h);
            $pdf->Cell(30,$row_h,'',0,0,'R',0);
            $pdf->Cell(30,$row_h,'______________________',0,0,'L',0);
            $pdf->Cell(70,$row_h,'Start Km.:',0,0,'R',0);
            $pdf->Cell(70,$row_h,'______________________',0,0,'L',0);
            $pdf->Ln($row_h);
            $pdf->Cell(30,$row_h,'',0,0,'R',0);
            $pdf->Cell(30,$row_h,'______________________',0,0,'L',0);
            $pdf->Cell(70,$row_h,'End Km.:',0,0,'R',0);
            $pdf->Cell(70,$row_h,'______________________',0,0,'L',0);
            $pdf->Ln($row_h);
            $pdf->Cell(30,$row_h,'Depart @:',0,0,'R',0);
            $pdf->Cell(30,$row_h,'____________________am',0,0,'L',0);
            $pdf->Cell(70,$row_h,'Return @:',0,0,'R',0);
            $pdf->Cell(70,$row_h,'____________________pm',0,0,'L',0);
            $pdf->Ln($row_h);
            $pdf->Ln($row_h);

            foreach($visits as $visit) {
                $r = 0;
                $data = $visit_base;
                
                $r ++;
                $data[0][$r] = 'category:';
                $data[1][$r] = $visit['category'];
                $data[2][$r] = '';

                $r ++;
                $data[0][$r] = 'Start time:';
                $data[1][$r] = $visit['time_from'];
                $data[2][$r] = '';
                $r ++;
                $data[0][$r] = 'Finish time:';
                $data[1][$r] = $visit['time_to'];
                $data[2][$r] = '';

                $r ++;
                $data[0][$r] = 'Service address:';
                $data[1][$r] = $visit['address'];
                $data[2][$r] = '';

                /*
                $r ++;
                $data[0][$r] = 'Contract notes:';
                $data[1][$r] = $visit['notes_admin'];
                $data[2][$r] = '';
                $r ++;
                */

                $r ++;
                $data[0][$r] = 'Visit notes:';
                $data[1][$r] = $visit['notes'];
                $data[2][$r] = '';

                $pdf->changeFont('H1');
                $pdf->Cell(20,$row_h,'Client :',0,0,'R',0);
                $pdf->Cell(20,$row_h,$visit['client'].', Contract code:'.$visit['client_code'],0,0,'L',0);
                $pdf->Ln($row_h);

                $pdf->Cell(20,$row_h,'Location :',0,0,'R',0);
                $pdf->Cell(20,$row_h,$visit['location'],0,0,'L',0);
                $pdf->Ln($row_h);

                $pdf->changeFont('H2');
                $pdf->Cell(20,$row_h,'Contact :',0,0,'R',0);
                $str = 'Confirmed with '.$visit['contact'];
                if($visit['contact_position'] !== '') $str .= '('.$visit['contact_position'].')';
                $str .= ' on '.$visit['date_booked'].' : ';
                if($visit['tel'] !== '') $str .= 'Tel-'.$visit['tel'].' ';
                if($visit['tel_alt'] !== '') $str .= ' / '.$visit['tel_alt'].' ';
                if($visit['cell'] !== '') $str .= 'Cell-'.$visit['cell'].' ';
                if($visit['cell_alt'] !== '') $str .= ' / '.$visit['cell_alt'].' ';
                $pdf->Cell(20,$row_h,$str,0,0,'L',0);
                $pdf->Ln($row_h);

                $pdf->changeFont('TEXT');
                $pdf->arrayDrawTable($data,$row_h2,$col_width,$col_type,'L',$pdf_options);
                $pdf->Ln($row_h);
                
                //get last visit
                $sql = 'SELECT V.`visit_id`,V.`user_id_tech`,U.`name` AS `technician`,V.`category_id`,C.`name` AS `category`, '.
                              'V.`date_booked`,V.`date_visit`,V.`notes`,V.`status`,V.`time_from`,V.`time_to`,V.`status` '.
                       'FROM `'.$table_visit.'` AS V '.
                             'LEFT JOIN `'.$table_category.'` AS C ON(V.`category_id` = C.`category_id`) '.   
                             'LEFT JOIN `'.$table_user.'` AS U ON(V.`user_id_tech` = U.`user_id`) '.
                       'WHERE V.`contract_id` = "'.$db->escapeSql($visit['contract_id']).'" AND V.`status` = "COMPLETED" AND '.
                             'V.`date_visit` < "'.$db->escapeSql($date).'"  '.
                       'ORDER BY V.`date_visit` DESC LIMIT 1';
                $last_visit = $db->readSqlRecord($sql);  
                if($last_visit == 0) {
                    $str = 'No previous visit on record.';
                } else {
                    $str = 'By '.$last_visit['technician'].' on '.Date::formatDate($last_visit['date_visit']).' for '.$last_visit['category'];
                } 
                $pdf->changeFont('H2');
                $pdf->Cell(20,$row_h,'Last visit :',0,0,'R',0);
                $pdf->Cell(20,$row_h,$str,0,0,'L',0);
                $pdf->Ln($row_h);
                $pdf->Ln($row_h);   
            }
            

            
                        
            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($doc_name,'D');    
            exit;
        }    
 

    }

    public static function contractOrphan($db,$type,$division_id,$options = [],&$error)
    {
        $error = '';

        $table_contract = TABLE_PREFIX.'contract';
        $table_client = TABLE_PREFIX.'client';
        $table_division = TABLE_PREFIX.'division';
        $table_invoice = TABLE_PREFIX.'contract_invoice';
        $table_visit = TABLE_PREFIX.'contract_visit';

        $date_from = $db->escapeSql($options['date_from']);
        $date_to = $db->escapeSql($options['date_to']);

        if(!isset($options['status'])) $options['status'] = 'ALL';
        if(!isset($options['type_id'])) $options['type_id'] = 'ALL';

        $sql_where = '';
        $type_str = '';

        if($options['type_id'] !== 'ALL') {
            $sql_where .= 'AND C.`type_id` = "'.$db->escapeSql($options['type_id']).'" ';
            $type_str = $options['type_id'].'_'; 
        }    
        
        if($options['status'] !== 'ALL') $sql_where .= 'AND C.`status` = "'.$db->escapeSql($options['status']).'"  ';
        
        if($division_id === 'ALL') {
            $base_doc_name = 'ALL_divisions';
            $page_title = 'All divisions';
        } else {
            $sql_where .= 'AND D.`division_id` = "'.$db->escapeSql($division_id).'" ';

            $division = Helpers::get($db,TABLE_PREFIX,'division',$division_id);
            if($division == 0) $error .= 'Invalid Division['.$division_id.'] selected.';

            $base_doc_name = str_replace(' ','_',$division['name']);
            $page_title = $division['name'];
        }

        $base_doc_name .= '_'.$options['status'].'_'.$type_str.'Contracts_without_';
        $page_title .= ' '.$options['status'].' '.$type_str.'Contracts without ';  

        $sql = 'SELECT C.`contract_id`,D.`name` AS `division`,C.`type_id` AS `type`,CL.`name` AS `client`,C.`client_code`,C.`date_signed` '.
               'FROM `'.$table_contract.'` AS C '.
               'JOIN `'.$table_division.'` AS D ON(C.`division_id` = D.`division_id`) '.
               'JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) ';
        if($type === 'INVOICE') {
            $base_doc_name .= 'invoices_';
            $page_title .= 'invoices ';
            //pdf table parameters
            $col_width=array(20,30,20,30,20,20);
            $col_type=array('','','','','','DATE');


            $sql .= 'LEFT JOIN `'.$table_invoice.'` AS I ON(C.`contract_id` = I.`contract_id` AND I.`date` >= "'.$date_from.'" AND I.`date` <= "'.$date_to.'") '.
                    'WHERE I.`invoice_no` IS NULL '.$sql_where.
                    'ORDER BY D.`name`, C.`date_signed` DESC ';
        }

        if($type === 'VISIT') {
            $base_doc_name .= 'visits_';
            $page_title .= 'visits ';
            //pdf table parameters
            $col_width=array(20,30,20,30,20,20);
            $col_type=array('','','','','','DATE');

            $sql .= 'LEFT JOIN `'.$table_visit.'` AS V ON(C.`contract_id` = V.`contract_id` AND V.`date_visit` >= "'.$date_from.'" AND V.`date_visit` <= "'.$date_to.'") '.
                    'WHERE V.`visit_id` IS NULL '.$sql_where.
                    'ORDER BY D.`name`, C.`date_signed` DESC ';
        }

        $base_doc_name .= 'from_'.$date_from.'_to_'.$date_to;
        $page_title .= 'from '.Date::formatDate($date_from).' to '.Date::formatDate($date_to);

        $result = $db->readSqlResult($sql);
        if($result == 0) $error .= 'NO contracts found matching your criteria!';

        if($error !== '') return false;

        if($options['format'] === 'PDF') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.pdf';
            
            $pdf = new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            
            $pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options = array();
            $pdf_options['font_size'] = 8;
            $row_h = 8;

            //$pdf->arrayDrawTable($data,$row_h,$col_width,$col_type,'C',$pdf_options);
            $pdf->mysqlDrawTable($result,$row_h,$col_width,$col_type,'L',$pdf_options);
                        
            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($doc_name,'D');    
            exit;
            
        }
        if($options['format']==='CSV') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.csv';
            //$csv_data = Csv::arrayDumpCsv($data); 
            $csv_data = Csv::mysqlDumpCsv($result);
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
            
        }
        
        if($options['format']==='HTML') {
            //$html = '<h1>'.$page_title.'</h1>';  
            $html_options = [];
            $html_options['col_type'] = $col_type; 
            //$html.=Html::arrayDumpHtml2($data,$html_options); 
            $html.=Html::mysqlDumpHtml($result,$html_options); 
            $html.='<br/>';
                  
            return $html;
        }
    }

    //Currently only supports Pastel Invoice import format and orkin specific settings, can generalise later
    public static function invoiceCsvExport($db,$division_id,$date_from,$date_to,$options = [],&$error)
    {
        $error = '';


        if(!isset($options['output'])) $options['output'] = 'BROWSER';
        if(!isset($options['format'])) $options['format'] = 'CSV';
        $options['format'] = strtoupper($options['format']);

        if($options['format'] !== 'CSV' and $options['format'] !== 'HTML') {
            $error .= 'Format '.$options['format'].' NOT available.';
        }    
        
        if($division_id === 'ALL') {
            $error .= 'Cannot run for ALL divisions. Please select an individual division.';
        } else {
            $division = Helpers::get($db,TABLE_PREFIX,'division',$division_id);
            if($division == 0) $error .= 'Invalid Division['.$division_id.'] selected.';

            //ttd: need to get this code directly from division setting rather than using tax_free flag
            if($division['tax_free']) $tax_type = '00'; else $tax_type = '15';
        }    
        
        if($error !== '') return false;

        $doc_name_base = str_replace(' ','_',$division['name']).'_division_invoices_from_'.
                         Date::formatDate($date_from).'_to_'.Date::formatDate($date_to).'_on_'.date('Y-m-d');

        $table_invoice = TABLE_PREFIX.'contract_invoice';
        $table_invoice_item = TABLE_PREFIX.'invoice_item';
        $table_contract = TABLE_PREFIX.'contract';
        $table_division = TABLE_PREFIX.'division';
        $table_client = TABLE_PREFIX.'client';
        $table_location = TABLE_PREFIX.'client_location';
        
        //NB: C.client_code is Contract/order no NOT client additional CL.client_code
        $sql = 'SELECT I.`invoice_id`,I.`invoice_no`,I.`date`,I.`subtotal`,I.`discount`,I.`tax`,I.`total`,I.`status`,I.`contract_id`, '.
                      'C.`client_id`,C.`client_code`,CL.`account_code`,L.`address` as `location_address` '.
               'FROM `'.$table_invoice.'` AS I JOIN `'.$table_contract.'` AS C ON(I.`contract_id` = C.`contract_id`) '.
                     'JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) '.
                     'JOIN `'.$table_location.'` AS L ON(C.`location_id` = L.`location_id`) '.
               'WHERE C.`division_id` = "'.$db->escapeSql($division_id).'" AND '.
                     'I.`date` >= "'.$db->escapeSql($date_from).'" AND I.`date` <= "'.$db->escapeSql($date_to).'" '.
               'ORDER BY I.`date`, I.`invoice_id` ';
                    
        $invoices = $db->readSqlArray($sql);
        if($invoices == 0) {
            $error .= 'No '.$division['name'].' division invoices found between '.Date::formatDate($date_from).' and '.Date::formatDate($date_to);
            return false;
        } 
        
        $clean_options = ['context' => 'input'];

        $csv_data = '';
        foreach($invoices as $invoice_id => $invoice) {

            $date = Date::mysqlGetDate($invoice['date']);
            $client = Helpers::getClient($db,TABLE_PREFIX,$invoice['client_id']);
            
            //Pastel crappy import code fails on a ' or "" and who knows what else
            $client_name = Secure::clean('string',$client['client']['company_title'],$clean_options);
            $client_contact_name = Secure::clean('string',$client['contact']['INVOICE']['name'],$clean_options);
            $client_contact_tel = Secure::clean('string',$client['contact']['INVOICE']['tel'],$clean_options);

            $location = explode("\n",$invoice['location_address']);
            for($i = 0; $i < 3; $i++) {
                //Pastel does not recognise " as an escape character so "" will blow it
                if(!isset($location[$i])) $location[$i] = ''; else $location[$i] = Secure::clean('string',$location[$i],$clean_options);
                //str_replace(["\r",'"'],'',$location[$i]);
            }
            $deliver_to = explode("\n",$client['location']['INVOICE']['address']);
            for($i = 0; $i < 5; $i++) {
                if(!isset($deliver_to[$i])) $deliver_to[$i] = ''; else $deliver_to[$i] = Secure::clean('string',$deliver_to[$i],$clean_options);
                //str_replace(["\r",'"'],'',$deliver_to[$i]);
            }

            $sql = 'SELECT `item_id`,`item_code`,`item_desc`,`quantity`,`units`,`unit_price`,`discount`,`tax`,`total` '.
                   'FROM `'.$table_invoice_item.'` WHERE `invoice_id` = "'.$db->escapeSql($invoice_id).'" ';
            $items = $db->readSqlArray($sql);

            if(INVOICE_SETUP['tax_inclusive']) $inclusive = 'Y'; else $inclusive = 'N';

            //assuming Feb financial year end
            $fin_period = $date['mon'] - 2;
            if($fin_period < 1) $fin_period += 12;

            $line = [];
            $line[] = 'Header';
            $line[] = Csv::csvPrep($invoice['invoice_no'],['type'=>'STRING']);     //document number, Character, 8 characters maximum, ignored when importing
            $line[] = ' ';                                      //Deleted, Character, Y=Deleted, <space>=not deleted, ignored when importing
            $line[] = ' ';                                      //Print Status, Character, Y=Printed, <space>=not printed
            $line[] = Csv::csvPrep($invoice['account_code'],['type'=>'STRING']);   //Customer Code, Character, 6 characters maximum
            $line[] = $fin_period;                             //Period Number, Numeric, 1-13
            $line[] = Date::formatDate($date,'ARRAY','DD-MM-YYYY',['separator'=>'/']); //Date, Character,DD/MM/YYYY
            $line[] = Csv::csvPrep($invoice['client_code']); //Order Number, Character, 25 characters maximum
            $line[] = $inclusive; //Inc/Exc, Character, Y=Inclusive, N=Exclusive 
            $line[] = Csv::csvPrep($invoice['discount']); //Discount, Numeric, nominal i assume
            //invoice message 1-3, 3 separate fields of 30 characters maximum each
            //$client_detail = $client['client']['company_title']."\n".$location['address'];
            $line[] = Csv::csvPrep($client_name);//$location[0];
            $line[] = Csv::csvPrep($location[0]);
            $line[] = Csv::csvPrep($location[1]);
            //Delivery Address 1-5, 5 separate fields of 30 characters maximum each
            $line[] = Csv::csvPrep($deliver_to[0]);
            $line[] = Csv::csvPrep($deliver_to[1]);
            $line[] = Csv::csvPrep($deliver_to[2]);
            $line[] = Csv::csvPrep($deliver_to[3]);
            $line[] = Csv::csvPrep($deliver_to[4]);
            
            $line[] = ''; //Sales Analysis Code, 5 characters maximum
            $line[] = '30'; //Settlement Terms, Numeric, 0-32 ??
            $line[] = Date::formatDate($date,'ARRAY','DD-MM-YYYY',['separator'=>'/']); //Document Date, Character,DD/MM/YYYY
            $line[] = Csv::csvPrep($client_contact_tel); //Telephone, 16 characters maximum CLIENT contact???
            $line[] = ''; //Fax number , 16 characters maximum
            $line[] = Csv::csvPrep($client_contact_name); //contact person, 16 characters maximum
            $line[] = '1'; //Exchange Rate, Numeric, 1 in foreign currency, 7.6 maximum 
            $line[] = ''; //Freight Method, 10 characters maximum
            $line[] = ''; //Ship/Deliver, 16 Characters maximum
            $line[] = 'N'; //Additional Costs, Y=Additional, N=Normal, supplier invoices only
            $line[] = ' '; //Email Status, Y=Emailed, <space>=not emailed

            $csv_line = implode(',',$line);
            Csv::csvAddRow($csv_line,$csv_data);

            //now process "Detail" item records
            foreach($items as $item) {
                $line = [];
                $line[] = 'Detail';
                $line[] = '0'; //Cost Price, Numeric, When you import, you MUST set this field to zero.
                $line[] = $item['quantity']; //Quantity, Numeric, 9.4 maximum
                $line[] = $item['unit_price']; //Unit Selling Price, Numeric, 9.4 maximum
                //NB: for some reason Pastel is happy with exclusive price total, maybe it just ignores this value and calculates itself
                $line[] = round(($item['quantity'] * $item['unit_price']),2); //Inclusive Price, Numeric, 9.4 maximum
                $line[] = Csv::csvPrep($item['units']); //Unit, Character, 4 maximum
                $line[] = $tax_type;//Tax type, Character, 00-30 (00=no taxation 1 = 14% 15 = 15% 02=zero rated, client dependant)
                //NB: Invoice items never have a discount, but if introducted should be as a %percentage
                if($item['discount'] != 0) {
                    $discount_type = '2';
                    $discount_pct = round($item['discount']*100,0); //ie: 12.5% = 1250
                } else {
                    $discount_type = '0';
                    $discount_pct = '0';
                }
                $line[] = $discount_type; //Discount type, Character, 0=None, 1=Settlement, 2=Invoice, 3=Both
                $line[] = $discount_pct; //Discount Percentage, Character, Omit decimals, for example 12.5% = 1250

                //Pastel does not recognise "/" code divider that itself exports!!
                $item_code = Csv::csvPrep(str_replace('/','',$item['item_code']));
                $item_desc = Secure::clean('string',$item['item_desc'],$clean_options);
                
                $line[] = Csv::csvPrep(substr($item_code,0,15)); //Code, Character, 15 maximum
                $line[] = Csv::csvPrep(substr($item_desc,-40)); //Descriptiom, Character, 40 maximum, counting from right!
                $line[] = '6'; //Line type, Character, 4=Inventory, 6=GL, 7=Remarks
                $line[] = ''; //Projects code, Character, 5 maximum
                $line[] = ''; //Store, Character, 3 maximum

                $csv_line = implode(',',$line);
                Csv::csvAddRow($csv_line,$csv_data);
            }
        }



        if($options['format'] === 'HTML') {
            $html = nl2br($csv_data);
        }

        if($options['format'] === 'CSV') {
            $doc_name = $doc_name_base.'.csv';
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD');
            exit();
        }               

        return $html;
    }

    public static function invoiceSummary($db,$division_id,$date_from,$date_to,$options = [],&$error)
    {
        $error = '';


        if(!isset($options['output'])) $options['output'] = 'BROWSER';
        if(!isset($options['format'])) $options['format'] = 'CSV';
        $options['format'] = strtoupper($options['format']);
        
        if($division_id === 'ALL') {
            $division_name = 'all divisions';
        } else {
            $division = Helpers::get($db,TABLE_PREFIX,'division',$division_id);
            if($division == 0) {
                $error .= 'Invalid Division['.$division_id.'] selected.';
            } else {
                $division_name = $division['name'];
            }    
        }    
        
        if($error !== '') return false;

        $doc_name_base = str_replace(' ','_',$division_name).'_invoices_from_'.
                         Date::formatDate($date_from).'_to_'.Date::formatDate($date_to).'_on_'.date('Y-m-d');

        $page_title = $division_name.' invoices from '.Date::formatDate($date_from).' to '.Date::formatDate($date_to);

        $table_invoice = TABLE_PREFIX.'contract_invoice';
        $table_invoice_item = TABLE_PREFIX.'invoice_item';
        $table_contract = TABLE_PREFIX.'contract';
        $table_division = TABLE_PREFIX.'division';
        $table_client = TABLE_PREFIX.'client';
        $table_location = TABLE_PREFIX.'client_location';
        
        //NB: C.client_code is Contract/order no NOT client additional CL.client_code
        $sql = 'SELECT I.`invoice_id`,I.`invoice_no`,I.`date`,I.`subtotal`,I.`discount`,I.`tax`,I.`total`,I.`status`,I.`contract_id`, '.
                      'C.`client_code` AS `contract_code`,CL.`name` AS `client`,CL.`account_code`,L.`address` AS `location_address` '.
               'FROM `'.$table_invoice.'` AS I JOIN `'.$table_contract.'` AS C ON(I.`contract_id` = C.`contract_id`) '.
                     'JOIN `'.$table_client.'` AS CL ON(C.`client_id` = CL.`client_id`) '.
                     'JOIN `'.$table_location.'` AS L ON(C.`location_id` = L.`location_id`) '.
               'WHERE I.`date` >= "'.$db->escapeSql($date_from).'" AND I.`date` <= "'.$db->escapeSql($date_to).'" ';
        if($division_id !== 'ALL') $sql .= 'AND C.`division_id` = "'.$db->escapeSql($division_id).'" ';       
               'ORDER BY I.`date`, I.`invoice_id` ';
                    
        $invoices = $db->readSqlResult($sql);
        if($invoices == 0) {
            $error .= 'No invoices found between '.Date::formatDate($date_from).' and '.Date::formatDate($date_to).' for '.$division_name;
            return false;
        } 
            

        $col_width = array(10,10,20,20,20,20,20,20,20,20,20,20,20);
        $col_type = array('','','','DBL2','DBL2','DBL2','DBL2','','','','','','');

        if($options['format'] === 'PDF') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.pdf';
            
            $pdf = new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            
            $pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options = [];
            $pdf_options['font_size'] = 8;
            $row_h = 6;
            
            $pdf->mysqlDrawTable($invoices,$row_h,$col_width,$col_type,'L',$pdf_options);

            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($doc_name,'D');    
            exit;
        }


        if($options['format'] === 'HTML') {
            $html_options = [];
            $html_options['col_type'] = $col_type;
            $html = Html::mysqlDumpHtml($invoices,$html_options);
        }

        if($options['format'] === 'CSV') {
            $doc_name = $base_doc_name.'_'.date('Y-m-d').'.csv';
            $csv_data = Csv::mysqlDumpCsv($invoices);
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
        }               

        return $html;
    }

    public static function contractValue($db,$division_id,$date_from,$date_to,$options = [],&$error)
    {
        $error = '';


        //if(!isset($options['output'])) $options['output'] = 'BROWSER';
        if(!isset($options['format'])) $options['format'] = 'HTML';
        $options['format'] = strtoupper($options['format']);

        if(!isset($options['user_id'])) $options['user_id'] = 'ALL';
        
        if($division_id === 'ALL') {
            $division_name = 'All divisions';
        } else {
            $division = Helpers::get($db,TABLE_PREFIX,'division',$division_id);
            if($division == 0) {
                $error .= 'Invalid Division['.$division_id.'] selected.';
            } else {
                $division_name = $division['name'];
            }    
        }

        if($options['user_id'] === 'ALL') {
            $user_name = 'all users';
        } else {
            $user = helpers::get($db,'',TABLE_USER,$options['user_id'],'user_id');
            if($user == 0) {
                $error .= 'Invalid User['.$options['user_id'].'] selected.';
            } else {
                $user_name = $user['name'];
            }    
        }     
        
        if($error !== '') return false;

        $doc_name_base = str_replace(' ','_',$division_name).'_contract_value_from_'.
                         Date::formatDate($date_from).'_to_'.Date::formatDate($date_to).'_on_'.date('Y-m-d');

        $page_title = $division_name.' contract value from '.Date::formatDate($date_from).' to '.Date::formatDate($date_to);

        $table_contract = TABLE_PREFIX.'contract';
        $table_division = TABLE_PREFIX.'division';
        $table_user = TABLE_USER;
        
        $sql_base = 'SELECT D.`division_id`,D.`name` AS `division`,U.`name` AS `user`, SUM(C.`price`) AS `total_price`, '.
                           'SUM(C.`price_visit`) AS `total_visit`, SUM(C.`price_audit`) AS `total_audit` '.
                    'FROM `'.$table_contract.'` AS C '.
                    'JOIN `'.$table_division.'` AS D ON(C.`division_id` = D.`division_id`) ';


        $sql_where = 'WHERE C.`date_signed` >= "'.$db->escapeSql($date_from).'" AND C.`date_signed` <= "'.$db->escapeSql($date_to).'" ';
        if($division_id !== 'ALL') $sql_where .= 'AND C.`division_id` = "'.$db->escapeSql($division_id).'" ';

        $sql_user = ['responsible'=>'','sold'=>'','signed'=>'','checked'=>''];
        foreach($sql_user as $key=>$sql) {
            $sql = $sql_base.' JOIN `'.$table_user.'` AS U ON(C.`user_id_'.$key.'` = U.`user_id`) '.$sql_where;
            if($options['user_id'] !== 'ALL') $sql .= 'AND C.`user_id_'.$key.'` = "'.$db->escapeSql($options['user_id']).'" ';
            $sql .= 'GROUP BY D.`division_id`,C.`user_id_'.$key.'` ';
            $sql_user[$key] = $sql;
        }
        

        $data_initial = [];
        $r = 0;
        $data_initial[0][$r] = 'Division';
        $data_initial[1][$r] = 'User';
        $data_initial[2][$r] = 'Total intitial VALUE';
        $data_initial[3][$r] = 'Total visit VALUE';
        $data_initial[4][$r] = 'Total audit VALUE';
                
        foreach($sql_user as $key=>$sql) {
            $r = 0;
            $data = $data_initial;
            $total = ['price'=>0,'visit'=>0,'audit'=>0];
            
            $arr = $db->readSqlArray($sql);
            if($arr != 0) {
                foreach($arr as $values) {
                    $r++;
                    $data[0][$r] = $values['division'];
                    $data[1][$r] = $values['user'];
                    $data[2][$r] = $values['total_price'];
                    $data[3][$r] = $values['total_visit'];
                    $data[4][$r] = $values['total_audit'];

                    $total['price'] .= $values['total_price']; 
                    $total['visit'] .= $values['total_visit'];
                    $total['audit'] .= $values['total_audit'];
                }
            }

            $r++;
            $data[0][$r] = 'Totals';
            $data[1][$r] = 'All users';
            $data[2][$r] = $total['price'];
            $data[3][$r] = $total['visit'];
            $data[4][$r] = $total['audit'];

            $data_user[$key] = $data;
        } 
            

        $col_width = array(30,30,20,20,20);
        $col_type = array('','','DBL2','DBL2','DBL2');

        if($options['format'] === 'PDF') {
            $doc_name = $doc_name_base.'_'.date('Y-m-d').'.pdf';
            
            $pdf = new Pdf('Portrait','mm','A4');
            $pdf->AliasNbPages();
              
            $pdf->setupLayout(['db'=>$db]);
            //change setup system setting if there is one
            $pdf->page_title = $page_title;
            
            $pdf->SetLineWidth(0.1);
            
            //$pdf->footer_text='footer';
    
            //NB footer must be set before this
            $pdf->AddPage();
            $pdf->changeFont('TEXT');
            $pdf_options = [];
            $pdf_options['font_size'] = 8;
            $row_h = 6;
            
            foreach($data_user as $key => $data) {
                $pdf->arrayDrawTable($data,$row_h,$col_width,$col_type,'L',$pdf_options); 
                $pdf->Ln($row_h*2);   
            }
            
            //$file_path=$pdf_dir.$pdf_name;
            //$pdf->Output($file_path,'F');  
    
            //finally create pdf file to browser
            $pdf->Output($doc_name,'D');    
            exit;
        }


        if($options['format'] === 'HTML') {
            $html = '<h1>'.$page_title.'</h1>';
            $html_options = [];
            $html_options['col_type'] = $col_type;
            
            foreach($data_user as $key => $data) {
                $html .= '<h2>'.strtoupper($key).' user</h2>';
                $html .= Html::arrayDumpHtml2($data,$html_options);
                $html .= '<br/>';
             }
            
        }

        if($options['format'] === 'CSV') {
            $csv_data = '';
            $doc_name = $doc_name_base.'_'.date('Y-m-d').'.csv';
            
            foreach($data_user as $key => $data) {
                $csv_data .= strtoupper($key).' user'."\r\n";
                $csv_data .= Csv::arrayDumpCsv($data);
                $csv_data .= "\r\n";
            }
            
            Doc::outputDoc($csv_data,$doc_name,'DOWNLOAD','csv');
            exit;
        }               

        return $html;
    }

}
