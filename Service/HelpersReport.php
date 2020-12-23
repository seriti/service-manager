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
use Seriti\Tools\Upload;
use Seriti\Tools\SITE_TITLE;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\STORAGE;
use Seriti\Tools\SITE_NAME;
use Seriti\Tools\TABLE_USER;
use Seriti\Tools\AJAX_ROUTE;

use Psr\Container\ContainerInterface;


//static functions for service module
class HelpersReport {
    
    public static function contractOrphan($db,$type,$division_id,$options = [],&$error)
    {
        $error = '';

        $table_contract = TABLE_PREFIX.'contract';
        $table_client = TABLE_PREFIX.'client';
        $table_division = TABLE_PREFIX.'division';
        $table_invoice = TABLE_PREFIX.'contract_invoice';
        $table_visit = TABLE_PREFIX.'contract_visit';

        $base_doc_name = 'Contracts_without_';
        $page_title = 'Contracts without ';

        $sql_where = '';
        if($division_id !== 'ALL') $sql_where .= 'AND D.division_id = "'.$db->escapeSql($division_id).'" ';

        $sql = 'SELECT C.contract_id,D.name AS division,C.type_id AS type,CL.name AS client,C.client_code,C.date_signed '.
               'FROM '.$table_contract.' AS C '.
               'JOIN '.$table_division.' AS D ON(C.division_id = D.division_id) '.
               'JOIN '.$table_client.' AS CL ON(C.client_id = CL.client_id) ';
        if($type === 'INVOICE') {
            $base_doc_name .= 'invoices';
            $page_title .= 'invoices';
            //pdf table parameters
            $col_width=array(20,30,20,30,20,20);
            $col_type=array('','','','','','DATE');


            $sql .= 'LEFT JOIN '.$table_invoice.' AS I ON(C.contract_id = I.contract_id )'.
                    'WHERE I.invoice_no IS NULL '.$sql_where.
                    'ORDER BY D.name, C.date_signed DESC ';
        }

        if($type === 'VISIT') {
            $base_doc_name .= 'visits';
            $page_title .= 'visits';
            //pdf table parameters
            $col_width=array(20,30,20,30,20,20);
            $col_type=array('','','','','','DATE');

            $sql .= 'LEFT JOIN '.$table_visit.' AS V ON(C.contract_id = V.contract_id )'.
                    'WHERE V.visit_id IS NULL '.$sql_where.
                    'ORDER BY D.name, C.date_signed DESC ';
        }

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

        $sql = 'SELECT I.invoice_id,I.invoice_no,I.date,I.subtotal,I.discount,I.tax,I.total,I.status,I.contract_id, '.
                      'C.client_id,CL.client_code,CL.account_code,L.address as location_address '.
               'FROM '.$table_invoice.' AS I JOIN '.$table_contract.' AS C ON(I.contract_id = C.contract_id) '.
                     'JOIN '.$table_client.' AS CL ON(C.client_id = CL.client_id) '.
                     'JOIN '.$table_location.' AS L ON(C.location_id = L.location_id) '.
               'WHERE C.division_id = "'.$db->escapeSql($division_id).'" AND '.
                     'I.date >= "'.$db->escapeSql($date_from).'" AND I.date <= "'.$db->escapeSql($date_to).'" '.
               'ORDER BY I.date, I.invoice_id ';
                    
        $invoices = $db->readSqlArray($sql);
        if($invoices == 0) {
            $error .= 'No '.$division['name'].' division invoices found between '.Date::formatDate($date_from).' and '.Date::formatDate($date_to);
            return false;
        } 
        
        $csv_data = '';
        foreach($invoices as $invoice_id => $invoice) {

            $date = Date::mysqlGetDate($invoice['date']);
            $client = Helpers::getClient($db,TABLE_PREFIX,$invoice['client_id']);
            
            $location = explode("\n",$invoice['location_address']);
            for($i = 0; $i < 3; $i++) {
                if(!isset($location[$i])) $location[$i] = ''; else $location[$i] = str_replace("\r",'',$location[$i]);
            }
            $deliver_to = explode("\n",$client['location']['INVOICE']['address']);
            for($i = 0; $i < 5; $i++) {
                if(!isset($deliver_to[$i])) $deliver_to[$i] = ''; else $deliver_to[$i] = str_replace("\r",'',$deliver_to[$i]);
            }

            $sql = 'SELECT item_id,item_code,item_desc,quantity,units,unit_price,discount,tax,total '.
                   'FROM '.$table_invoice_item.' WHERE invoice_id = "'.$db->escapeSql($invoice_id).'" ';
            $items = $db->readSqlArray($sql);

            $line = [];
            $line[] = 'Header';
            $line[] = Csv::csvPrep($invoice['invoice_no']);     //document number, Character, 8 characters maximum, ignored when importing
            $line[] = ' ';                                      //Deleted, Character, Y=Deleted, <space>=not deleted, ignored when importing
            $line[] = 'Y';                                      //Print Status, Character, Y=Printed, <space>=not printed
            $line[] = Csv::csvPrep($invoice['account_code']);   //Customer Code, Character, 6 characters maximum
            $line[] = $date['mon'];                             //Period Number, Numeric, 1-13
            $line[] = Date::formatDate($date,'ARRAY','DD-MM-YYYY',['separator'=>'/']); //Date, Character,DD/MM/YYYY
            $line[] = ''; //Order Number, Character, 25 characters maximum
            $line[] = 'Y'; //Inc/Exc, Character, Y=Inclusive, N=Exclusive 
            $line[] = Csv::csvPrep($invoice['discount']); //Discount, Numeric, nominal i assume
            //invoice message 1-3, 3 separate fields of 30 characters maximum each
            $line[] = '';//$location[0];
            $line[] = '';//$location[1];
            $line[] = '';//$location[2];
            //Delivery Address 1-5, 5 separate fields of 30 characters maximum each
            $line[] = $deliver_to[0];
            $line[] = $deliver_to[1];
            $line[] = $deliver_to[2];
            $line[] = $deliver_to[3];
            $line[] = $deliver_to[4];
            
            $line[] = ''; //Sales Analysis Code, 5 characters maximum
            $line[] = '30'; //Settlement Terms, Numeric, 0-32 ??
            $line[] = Date::formatDate($date,'ARRAY','DD-MM-YYYY',['separator'=>'/']); //Document Date, Character,DD/MM/YYYY
            $line[] = $client['contact']['INVOICE']['tel']; //Telephone, 16 characters maximum CLIENT contact???
            $line[] = ''; //Fax number , 16 characters maximum
            $line[] = $client['contact']['INVOICE']['name']; //contact person, 16 characters maximum
            $line[] = '1'; //Exchange Rate, Numeric, 1 in foreign currency, 7.6 maximum 
            $line[] = ''; //Freight Method, 10 characters maximum
            $line[] = ''; //Ship/Deliver, 16 Characters maximum
            $line[] = 'N'; //Additional Costs, Y=Additional, N=Normal, supplier invoices only
            $line[] = 'Y'; //Email Status, Y=Emailed, <space>=not emailed

            $csv_line = implode(',',$line);
            Csv::csvAddRow($csv_line,$csv_data);

            //now process "Detail" item records
            foreach($items as $item) {
                $line = [];
                $line[] = 'Detail';
                $line[] = '0'; //Cost Price, Numeric, When you import, you MUST set this field to zero.
                $line[] = $item['quantity']; //Quantity, Numeric, 9.4 maximum
                $line[] = $item['unit_price']; //Unit Selling Price, Numeric, 9.4 maximum
                $line[] = $item['total']; //Inclusive Price, Numeric, 9.4 maximum
                $line[] = Csv::csvPrep($item['units']); //Unit, Character, 4 maximum
                $line[] = '01'; //Tax type, Character, 00-30 (00=no taxation 02=zero rated)
                //NB: Invoice items never have a discount, but if introducted should be as a %percentage
                if($item['discount'] != 0) {
                    $discount_type = '2';
                    $discount_pct = round($item['discount']*100,0); //12.5% = 1250
                } else {
                    $discount_type = '0';
                    $discount_pct = '0';
                }
                $line[] = $discount_type; //Discount type, Character, 0=None, 1=Settlement, 2=Invoice, 3=Both
                $line[] = $discount_pct; //Discount Percentage, Character, Omit decimals, for example 12.5% = 1250
                $line[] = Csv::csvPrep(substr($item['item_code'],0,15)); //Code, Character, 15 maximum
                $line[] = Csv::csvPrep(substr($item['item_desc'],0,40)); //Descriptiom, Character, 40 maximum
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

}
