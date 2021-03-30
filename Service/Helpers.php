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
class Helpers {
    public static function checkTimeout($time_start,$time_max,$time_tolerance=5) {
        if ($time_start == 0 or $time_max == 0) return false;
          
        $time_passed = time()-$time_start;
        $time_trigger = $time_max-$time_tolerance;
              
        if($time_passed > $time_trigger) return true; return false;
    }

    //generic record get, add any exceptions you want
    public static function get($db,$table_prefix,$table,$id,$key = '') 
    {
        $table_name = $table_prefix.$table;

        if($key === '') $key = $table.'_id';    
        
        $sql = 'SELECT * FROM '.$table_name.' WHERE '.$key.' = "'.$db->escapeSql($id).'" ';
        
        $record = $db->readSqlRecord($sql);
                        
        return $record;
    } 

    //get contract items & service visit items for invoice creation
    public static function getInvoiceItems($db,$table_prefix,$contract_id,$format = 'ARRAY') 
    {
        $table_visit = $table_prefix.'contract_visit';
        $table_visit_item = $table_prefix.'visit_item';
        //$table_contract = $table_prefix.'contract';
        $table_contract_item = $table_prefix.'contract_item';
        $table_item = $table_prefix.'service_item';
        $table_units = $table_prefix.'item_units';
        $table_account_code = $table_prefix.'account_code';

        $invoice_items = [];
        $totals = ['subtotal'=>0,'discount'=>0,'tax'=>0,'total'=>0];

        $contract = self::get($db,$table_prefix,'contract',$contract_id);

        
        $contract_info = '';
        if(INVOICE_SETUP['last_visit_info']) {
            //get most recent completed(ie NOT invoiced) visit
            $sql = 'SELECT V.visit_id,V.date_visit,V.service_no,V.notes '.
                   'FROM '.$table_visit.' AS V '.
                   'WHERE V.contract_id = "'.$contract['contract_id'].'" AND V.status = "COMPLETED" AND V.service_no <> "" '.
                   'ORDER BY V.date_visit DESC LIMIT 1';
            $last_visit = $db->readSqlRecord($sql);
            if($last_visit != 0) $contract_info = ' '.$last_visit['notes'];
        }

        if(INVOICE_SETUP['contract_item'] === 'account_code' and $contract['account_code'] !== '') {
            $contract_item_code = $contract['account_code'];

            if(INVOICE_SETUP['account_info']) {
                $sql = 'SELECT description FROM '.$table_account_code.' WHERE code = "'.$contract['account_code'].'" ';
                $str = $db->readSqlValue($sql,'');
                if($str === '') $str = $contract['account_code'];
                $contract_info .= ' - '.$str;
            } 
        } else {
            $contract_item_code = $contract['client_code'];
        }       
        
        
        if($contract['type_id'] === 'SINGLE') {
            $invoice_item = [];
            $invoice_item['code'] = $contract_item_code;
            $invoice_item['name'] = 'Single Contract: '.$contract['client_code'].$contract_info;
            $invoice_item['quantity'] = 1;
            $invoice_item['units'] = '';
            $invoice_item['price'] = $contract['price'];
            $invoice_item['notes'] = $contract['notes_client'];

            if($contract['discount'] <= 50) {
                $totals['discount'] = round(($contract['price'] * $contract['discount']/100),2);
            } else {
                $totals['discount'] = $contract['discount'];
            }    
            $invoice_items[] = $invoice_item;
        }

        if($contract['type_id'] === 'REPEAT') {
            /*
            $sql = 'SELECT COUNT(*) FROM '.$table_visit.' '.
                   'WHERE contract_id = "'.$contract['contract_id'].'" AND (status = "COMPLETED" OR status = "INVOICED")';
            $count = $db->readSqlValue($sql,0);
            */
            //NB: no_visits must be updated by invoice creation
            //NOT sure if i want to get too clever, can just count actual visits.
            //use no_visits to determine frequency of visits rather than visit counter??
            if($contract['no_visits'] == 0) {
                $price = $contract['price'];
                //$visit_no = 1;
            } else {
                //$visit_no = $contract['no_visits'] + 1;
                $price = $contract['price_visit'];
            }    

            $invoice_item = [];
            $invoice_item['code'] = $contract_item_code;
            $invoice_item['name'] = 'Repeat Contract: '.$contract['client_code'].$contract_info;//' Visit-'.$visit_no ;
            $invoice_item['quantity'] = 1;
            $invoice_item['units'] = '';
            $invoice_item['price'] = $price;
            $invoice_item['notes'] = $contract['notes_client'];

            $invoice_items[] = $invoice_item;
        }

        

        $sql = 'SELECT C.item_id,I.name,I.code,C.price,U.name AS units,C.notes '.
               'FROM '.$table_contract_item.' AS C LEFT JOIN '.$table_item.' AS I ON(C.item_id = I.item_id) '.
                     'LEFT JOIN '.$table_units.' AS U ON(I.units_id = U.units_id) '.
               'WHERE C.contract_id = "'.$contract['contract_id'].'" AND C.price > 0 ';
        $contract_items = $db->readSqlArray($sql);

        //NB: this assumes that contract visit record status is updated after invoicing, and also that a service slip no has been captured
        //NB2: status should be set to INVOICED after invoices processed
        $sql = 'SELECT VI.data_id,V.date_visit,V.service_no,VI.item_id,I.name,I.code,U.name AS units,VI.quantity,VI.price,VI.notes '.
               'FROM '.$table_visit.' AS V JOIN '.$table_visit_item.' AS VI ON(V.visit_id = VI.visit_id AND VI.price > 0) '.
                     'LEFT JOIN '.$table_item.' AS I ON(VI.item_id = I.item_id) '.
                     'LEFT JOIN '.$table_units.' AS U ON(I.units_id = U.units_id) '.
               'WHERE V.contract_id = "'.$contract['contract_id'].'" AND V.status = "COMPLETED" AND V.service_no <> "" '.
               'ORDER BY V.date_visit ';
        $visit_items = $db->readSqlArray($sql);
        
        if($contract_items != 0) {
            foreach($contract_items as $item_id => $item) {
                $invoice_item = [];
                //$invoice_item['item_id'] = $item_id;
                $invoice_item['code'] = $item['code'];
                $invoice_item['name'] = $item['name'];
                $invoice_item['quantity'] = 1;
                $invoice_item['units'] = $item['units'];
                $invoice_item['price'] = $item['price'];
                $invoice_item['notes'] = 'Contract item';

                $invoice_items[] = $invoice_item;
            } 
        }

        if($visit_items != 0) {
            foreach($visit_items as $item_id => $item) {
                $invoice_item = [];
                //$invoice_item['item_id'] = $item_id;
                $invoice_item['code'] = $item['code'];
                $invoice_item['name'] = $item['name'];
                $invoice_item['quantity'] = $item['quantity'];
                $invoice_item['units'] = $item['units'];
                $invoice_item['price'] = $item['price'];
                $invoice_item['notes'] = 'Service slip:'.$item['service_no'];

                $invoice_items[] = $invoice_item;
            } 
        }

        foreach($invoice_items as $item) {
            $item_price = $item['quantity'] * $item['price'];
            $totals['subtotal'] += round($item_price,2);
        }

        $totals['tax'] = round((($totals['subtotal'] - $totals['discount']) * TAX_RATE),2);
        $totals['total'] = $totals['subtotal'] - $totals['discount'] + $totals['tax'];
        
        if($format === 'ARRAY') {
            $output['items'] = $invoice_items;
            $output['totals'] = $totals;
        }    
        if($format === 'HTML') {
            $output = '<h1>Contract ID['.$contract_id.'] '.$contract['client_code'].'</h1>'.
                      Html::arrayDumpHtml($invoice_items).
                      Html::recordDumpHtml($totals,['layout'=>'COLUMN','header'=>false]);
        }    
                        
        return $output;
    } 

    public static function saveInvoice($db,$table_prefix,$contract_id,$invoice_note,&$error)
    {
        $error = '';
        $invoice_id = 0;

        $table_division = $table_prefix.'division';
        $table_invoice = $table_prefix.'contract_invoice';
        $table_item = $table_prefix.'invoice_item';

        $contract = self::get($db,$table_prefix,'contract',$contract_id);
        $division = self::get($db,$table_prefix,'division',$contract['division_id']);
       
        $invoice_no = $division['invoice_prefix'].($division['invoice_no']+1);

        //check invoice_no unique
        $sql='SELECT * FROM '.$table_invoice.' WHERE invoice_no = "'.$db->escapeSql($invoice_no).'" ';
        $invoice_dup=$db->readSqlRecord($sql);
        if($invoice_dup!=0) $error .=' Invoice No['.$invoice_no.'] has been used before!'; 
           
        $invoice = self::getInvoiceItems($db,$table_prefix,$contract_id,'ARRAY'); 

        if($error !== '') return false;

        $data = [];
        $data['contract_id'] = $contract_id;
        $data['invoice_no'] = $invoice_no;
        $data['subtotal'] = $invoice['totals']['subtotal'];
        $data['discount'] = $invoice['totals']['discount'];
        $data['tax'] = $invoice['totals']['tax'];
        $data['total'] = $invoice['totals']['total'];
        $data['date'] = date('Y-m-d');
        $data['notes'] = $invoice_note;
        $data['status'] = 'NEW';

        $invoice_id = $db->insertRecord($table_invoice,$data,$error_tmp);
        if($error_tmp !== '') throw new Exception('SERVICE_INVOICE_CREATE: Could not create contract invoice');

        foreach($invoice['items'] as $item) {
            $data = [];
            $data['invoice_id'] = $invoice_id;
            $data['item_code'] = $item['code'];
            $data['item_desc'] = $item['name'];
            $data['quantity'] = $item['quantity'];
            $data['units'] = $item['units'];
            $data['unit_price'] = $item['price'];
            $data['discount'] = 0;
            $total = $item['quantity'] * $item['price'];
            $tax = $total * TAX_RATE;
            $data['tax'] = round($tax,2);
            $data['total'] = round($total,2);

            $db->insertRecord($table_item,$data,$error_tmp);
            if($error_tmp !== '') throw new Exception('SERVICE_INVOICE_CREATE: Could not create contract invoice item');

        } 

        //finally update division invoice counter
        $sql = 'UPDATE '.$table_division.' SET invoice_no = invoice_no + 1 WHERE division_id = "'.$contract['division_id'].'" ';
        $db->executeSql($sql,$error_tmp);
        if($error_tmp !== '') throw new Exception('SERVICE_INVOICE_CREATE: Could not update division invoice no');

        return $invoice_id;
    }

    public static function createInvoicePdf_old($db,ContainerInterface $container,$invoice_id,&$doc_name,&$error)
    {
        $error = '';
        $pdf_dir = BASE_UPLOAD.UPLOAD_DOCS;
        //for custom settings like signature
        $upload_dir = BASE_UPLOAD.UPLOAD_DOCS;

        $table_items = TABLE_PREFIX.'invoice_item';
        $system = $container->system;
        
        $invoice = self::get($db,TABLE_PREFIX,'contract_invoice',$invoice_id,'invoice_id');

        $contract = self::get($db,TABLE_PREFIX,'contract',$invoice['contract_id']);
        $client = self::get($db,TABLE_PREFIX,'client',$contract['client_id']);
        $location = self::get($db,TABLE_PREFIX,'client_location',$contract['location_id'],'location_id');
        //$contact = self::get($db,TABLE_PREFIX,'client_contact',$contract['contact_id'],'contact_id');

        $sql = 'SELECT item_id,item_code,item_desc,quantity,units,unit_price,discount,tax,total '.
               'FROM '.$table_items.' WHERE invoice_id = "'.$db->escapeSql($invoice_id).'" ';
        $items = $db->readSqlArray($sql); 

     
        //invoice_no must be unique
        $pdf_name = 'INV-'.$invoice['invoice_no'].'.pdf';
        $doc_name = $pdf_name;
                
        //get setup options
        $footer = $system->getDefault('INVOICE_FOOTER','');
        $signature = $system->getDefault('INVOICE_SIGN','');
        $signature_text = $system->getDefault('INVOICE_SIG_TXT','');
                    
        $pdf = new Pdf('Portrait','mm','A4');
        $pdf->AliasNbPages();
            
        $pdf->setupLayout(['db'=>$db]);
        
        //NB footer must be set before this
        $pdf->AddPage();

        $row_h = 5;
                                 
        $pdf->SetY(40);
        $pdf->changeFont('H1');
        $pdf->Cell(30,$row_h,'INVOICE :',0,0,'R',0);
        $pdf->Cell(30,$row_h,$invoice['invoice_no'],0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Cell(30,$row_h,'To :',0,0,'R',0);
        $pdf->Cell(30,$row_h,$client['name'],0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Cell(30,$row_h,'At :',0,0,'R',0);
        $pdf->Cell(30,$row_h,$location['name'],0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Cell(30,$row_h,'Date issued :',0,0,'R',0);
        $pdf->Cell(30,$row_h,date('j-F-Y'),0,0,'L',0);
        $pdf->Ln($row_h);
                
        $pdf->Ln($row_h);
        
        if(count($items) != 0) {
            
            $arr = [];
            $r = 0;
            $arr[0][$r] = 'Code';
            $arr[1][$r] = 'Description';
            $arr[2][$r] = 'Quantity';
            $arr[3][$r] = 'Price';
            $arr[4][$r] = 'Discount';
            $arr[5][$r] = 'Tax';
            $arr[6][$r] = 'Total';
            foreach($items as $item) {
                $r++;
                $arr[0][$r] = $item['item_code'];
                $arr[1][$r] = $item['item_desc'];
                $arr[2][$r] = $item['quantity'];
                $arr[3][$r] = $item['unit_price'];
                $arr[4][$r] = $item['discount'];
                $arr[5][$r] = $item['tax'];
                $arr[6][$r] = $item['total'];
            }


            $pdf->changeFont('TEXT');
            //item_id,item_code,item_desc,quantity,units,unit_price,discount,tax,total
            $col_width = array(20,40,20,20,20,20,20);
            $col_type = array('','','','DBL1','DBL2','DBL2','DBL2');
            $pdf->arrayDrawTable($arr,$row_h,$col_width,$col_type,'L');
        }
        
        //totals
        $pdf->changeFont('H3');
        $pdf->Cell(142,$row_h,'SUBTOTAL :',0,0,'R',0);
        $pdf->Cell(142,$row_h,number_format($invoice['subtotal'],2),0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Cell(142,$row_h,'DISCOUNT :',0,0,'R',0);
        $pdf->Cell(142,$row_h,number_format($invoice['discount'],2),0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Cell(142,$row_h,'TAX :',0,0,'R',0);
        $pdf->Cell(142,$row_h,number_format($invoice['tax'],2),0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Cell(142,$row_h,'TOTAL :',0,0,'R',0);
        $pdf->Cell(142,$row_h,number_format($invoice['total'],2),0,0,'L',0);
        $pdf->Ln($row_h);
        $pdf->Ln($row_h);
            
        if($invoice['notes'] != '') {
            $pdf->MultiCell(0,$row_h,$invoice['notes'],0,'L',0); 
            $pdf->Ln($row_h);
        }
                
        //initialise text block with custom footer text, if any.
        $txt = $footer;
                    
        if($signature != '') {
            $image_path = $upload_dir.$signature;
            list($img_width,$img_height) = getimagesize($image_path);
            //height specified and width=0 so auto calculated     
            $y1 = $pdf->GetY();
            $pdf->Image($image_path,20,$y1,0,20);
            //$pdf->Image('images/sig_XXX.jpg',20,$y1,66,20);
            $pdf->SetY($y1+25);
        } else {
            $pdf->Ln($row_h*3); 
        }   
        
        if($signature_text != '') {    
            $pdf->Cell(0,$row_h,$signature_text,0,0,'L',0);
            $pdf->Ln($row_h);
        }  
                
        //finally create pdf file
        $file_path = $pdf_dir.$pdf_name;
        $pdf->Output($file_path,'F'); 

        if($error === '') {
            self::saveInvoicePdf($db,$container->s3,$invoice_id,$doc_name,$error);  
        }    
                
        if($error == '') return true; else return false ;
    }

    public static function createInvoicePdf($db,ContainerInterface $container,$invoice_id,&$doc_name,&$error)
    {
        $error = '';
        $pdf_dir = BASE_UPLOAD.UPLOAD_DOCS;
        //for custom settings like signature
        $upload_dir = BASE_UPLOAD.UPLOAD_DOCS;

        $table_items = TABLE_PREFIX.'invoice_item';
        $system = $container->system;
        
        $invoice = self::get($db,TABLE_PREFIX,'contract_invoice',$invoice_id,'invoice_id');
        $contract = self::get($db,TABLE_PREFIX,'contract',$invoice['contract_id']);
        $division = self::get($db,TABLE_PREFIX,'division',$contract['division_id']);
        $location = self::get($db,TABLE_PREFIX,'client_location',$contract['location_id'],'location_id');
        
        //get client and all primary contact and location details 
        $client = self::getClient($db,TABLE_PREFIX,$contract['client_id']);

        $sql = 'SELECT item_id,item_code,item_desc,quantity,units,unit_price,discount,tax,total '.
               'FROM '.$table_items.' WHERE invoice_id = "'.$db->escapeSql($invoice_id).'" '.
               'ORDER BY total DESC ';
        $items = $db->readSqlArray($sql); 

     
        //invoice_no must be unique
        $pdf_name = 'INV-'.$invoice['invoice_no'].'.pdf';
        $doc_name = $pdf_name;
                
        $pdf = new InvoicePdf('Portrait','mm','A4');
        $pdf->AliasNbPages();
            
        $pdf->setupLayout(['db'=>$db]);

        //NB: override PDF defaults
        //NB: h1_title only relevant to header
        //$pdf->h1_title = array(33,33,33,'B',10,'',5,10,'L','YES',33,33,33,'B',12,20,180); //NO date
        //$pdf->bg_image = array('images/logo.jpeg',5,140,50,20,'YES'); //NB: YES flag turns off logo image display
        $pdf->page_margin = array(115,10,10,50);//top,left,right,bottom!!
        //$pdf->text = array(33,33,33,'',8);
        $pdf->SetMargins($pdf->page_margin[1],$pdf->page_margin[0],$pdf->page_margin[2]);

        //assign invoice HEADER data 
        $pdf->addTextElement('business_title',$division['invoice_title']);
        $pdf->addTextElement('doc_name','Invoice');
        $pdf->addTextElement('doc_date',$invoice['date']);
        $pdf->addTextElement('doc_no',$invoice['invoice_no']);

       
        $pdf->addTextBlock('business_address',$division['invoice_address']);
        $pdf->addTextBlock('business_contact',$division['invoice_contact']);

        $client_detail = $client['client']['company_title']."\n".$location['address'];
        $pdf->addTextBlock('client_detail',$client_detail);
        $pdf->addTextBlock('client_deliver',$client['location']['INVOICE']['address']);

        $pdf->addTextElement('acc_no',$client['client']['account_code']);
        $pdf->addTextElement('acc_ref',$contract['client_code']);
        $pdf->addTextElement('acc_tax_exempt','N');
        $pdf->addTextElement('acc_tax_ref',$client['client']['tax_reference']);
        $pdf->addTextElement('acc_sales_code',$client['client']['sales_code']);

        //assign invoice FOOTER data 
        $pdf->addTextBlock('total_info',$division['invoice_info']); //can be anything but normaly banking data
        $pdf->addTextElement('total_sub',number_format($invoice['subtotal'],2));
        $pdf->addTextElement('total_discount',number_format($invoice['discount'],2));
        $pdf->addTextElement('total_ex_tax',number_format(($invoice['subtotal'] - $invoice['discount']),2));
        $pdf->addTextElement('total_tax',number_format($invoice['tax'],2));
        $pdf->addTextElement('total',number_format($invoice['total'],2));

        //NB footer must be set before this
        $pdf->AddPage();

        $row_h = 5;

        //$pdf->SetY(120);
        //$pdf->Ln($row_h);
        $frame_y = $pdf->getY();
        
        if(count($items) != 0) {
            
            $arr = [];
            $r = 0;
            $arr[0][$r] = 'Code';
            $arr[1][$r] = 'Description';
            $arr[2][$r] = 'Quantity';
            $arr[3][$r] = 'Price';
            $arr[4][$r] = 'Discount';
            $arr[5][$r] = 'Tax';
            $arr[6][$r] = 'Total';
            
            foreach($items as $item) {
                $r++;
                $arr[0][$r] = $item['item_code'];
                $arr[1][$r] = $item['item_desc'];
                $arr[2][$r] = number_format($item['quantity'],0).$item['units'];
                $arr[3][$r] = $item['unit_price'];
                $arr[4][$r] = $item['discount'];
                $arr[5][$r] = $item['tax'];
                $arr[6][$r] = $item['total'];
            }
                         
            $pdf->changeFont('TEXT');
            //item_id,item_code,item_desc,quantity,units,unit_price,discount,tax,total
            $col_width = array(20,75,20,20,20,20,25);
            $col_type = array('','','','DBL2','DBL2','DBL2','DBL2');
            $table_options['resize_cols'] = true;
            $table_options['format_header'] = ['line_width'=>0.1,'fill'=>'#FFFFFF','line_color'=>'#000000'];
            $table_options['format_text'] = ['line_width'=>0.1]; //['line_width'=>-1];
            $table_options['header_align'] = 'L';
            $pdf->arrayDrawTable($arr,$row_h,$col_width,$col_type,'L',$table_options);
        }
        
        if($invoice['notes'] != '') {
            $pdf->MultiCell(0,$row_h,$invoice['notes'],0,'L',0); 
            $pdf->Ln($row_h);
        }

        $pdf->changeFont('H2');
        $pdf->SetLineWidth(.1);
        $pdf->SetDrawColor(0,0,0);
        $pos_x = 10;
        $pos_y = $frame_y;
        $width = 190;
        $height = $pdf->GetY() - $frame_y;
        $pdf->Rect($pos_x,$pos_y,$width,$height,'D');
                
        //finally create pdf file
        $file_path = $pdf_dir.$pdf_name;
        $pdf->Output($file_path,'F'); 

        if($error === '') {
            //comment out and then can view pdf in storage/docs withpout uploading to amazon etc
            self::saveInvoicePdf($db,$container->s3,$invoice_id,$doc_name,$error);  
        }    
                
        if($error == '') return true; else return false ;
    }

    public static function saveInvoicePdf($db,$s3,$invoice_id,$doc_name,&$error) {
        $error_tmp = '';
        $error = '';
     
        $pdf_dir = BASE_UPLOAD.UPLOAD_DOCS; 
        
        $location_id = 'INV'.$invoice_id;
        $file_id = Calc::getFileId($db);
        $file_name = $file_id.'.pdf';
        $pdf_path_old = $pdf_dir.$doc_name;
        $pdf_path_new = $pdf_dir.$file_name;
        //rename doc to new guaranteed non-clashing name
        if(!rename($pdf_path_old,$pdf_path_new)) {
            $error .= 'Could not rename invoice pdf!<br/>'; 
        } 
                
        //create file records and upload to amazon if required
        if($error == '') {    
            $file = array();
            $file['file_id'] = $file_id; 
            $file['file_name'] = $file_name;
            $file['file_name_orig'] = $doc_name;
            $file['file_ext'] = 'pdf';
            $file['file_date'] = date('Y-m-d');
            $file['location_id'] = $location_id;
            $file['encrypted'] = false;
            $file['file_size'] = filesize($pdf_path_new); 
            
            if(STORAGE === 'amazon') {
                $s3->putFile($file['file_name'],$pdf_path_new,$error_tmp); 
                if($error_tmp !== '') $error.='Could NOT upload files to Amazon S3 storage!<br/>';
            } 
            
            if($error == '') {
                $db->insertRecord(TABLE_PREFIX.'file',$file,$error_tmp);
                if($error_tmp != '') $error .= 'ERROR creating invoice file record: '.$error_tmp.'<br/>';
            }   
        }   
               
        
        if($error == '') return $invoice_id; else return false;
    }


    //email invoice to client or any other specified email address
    public static function sendInvoice($db,ContainerInterface $container,$invoice_id,&$mail_to,&$error_str) {
        $error_str = '';
        $error_tmp = '';
        $attach_msg = '';
                
        $system = $container['system'];
        $mail = $container['mail'];

        $invoice = self::get($db,TABLE_PREFIX,'contract_invoice',$invoice_id,'invoice_id');
        $contract = self::get($db,TABLE_PREFIX,'contract',$invoice['contract_id']);
        $location = self::get($db,TABLE_PREFIX,'client_location',$contract['location_id'],'location_id');
        $contact = self::get($db,TABLE_PREFIX,'client_contact',$contract['contact_id'],'contact_id');

        //get client and location and contact defaults         
        $client = self::getClient($db,TABLE_PREFIX,$contract['client_id']);
        if($mail_to === 'DEFAULT') {
            if($client['contact']['INVOICE']['email'] === '') {
                $error_str .= 'Client contact['.$client['contact']['INVOICE']['name'].'] does not have an email assigned!';
            } else {
                $mail_to = $client['contact']['INVOICE']['email'];
            }
        }    
        
        if($error_str !== '') return false;  

        //get all files related to invoice
        $attach = array();
        $attach_file = array();

        //NB: only using for download, all files associated with invoice will be attached
        $docs = new Upload($db,$container,TABLE_PREFIX.'file');
        $docs->setup(['location'=>'INV','interface'=>'download']);

        $sql = 'SELECT file_id,file_name_orig FROM '.TABLE_PREFIX.'file '.
               'WHERE location_id ="INV'.$invoice_id.'" ORDER BY file_id ';
        $invoice_files = $db->readSqlList($sql);
        if($invoice_files != 0) {
            foreach($invoice_files as $file_id => $file_name) {
                $attach_file['name'] = $file_name;
                $attach_file['path'] = $docs->fileDownload($file_id,'FILE'); 
                if(substr($attach_file['path'],0,5) !== 'Error' and file_exists($attach_file['path'])) {
                    $attach[] = $attach_file;
                    $attach_msg .= $file_name."\r\n";
                } else {
                    $error_str .= 'Error fetching files for attachment to email!'; 
                }   
            }   
        }
            
        //configure and send email
        if($error_str == '') {
            $subject = SITE_NAME.' invoice '.$invoice['invoice_no'];
            $body = 'Attention: '.$client['contact']['INVOICE']['name']."\r\n".
                    'Contract: '.$contract['client_code']."\r\n".
                    'Location: '.$location['name']."\r\n\r\n".
                    'Please see attached invoice and any supporting documents.'."\r\n\r\n";
                        
            if($attach_msg != '') $body .= 'All documents attached to this email: '."\r\n".$attach_msg."\r\n";
                        
            $mail_footer = $system->getDefault('SRV_EMAIL_FOOTER','');
            $body .= $mail_footer."\r\n";
                        
            $param = ['attach'=>$attach];
            $mail->sendEmail('',$mail_to,$subject,$body,$error_tmp,$param);
            if($error_tmp != '') { 
                $error_str .= 'Error sending invoice email with attachments to email['. $mail_to.']:'.$error_tmp; 
            }       
        }  
            
        if($error_str == '') return true; else return false;  
    } 

    public static function getContractCode($db,$table_prefix,$division_id) 
    {
        $error = '';

        $division = self::get($db,$table_prefix,'division',$division_id);
        $no = $division['contract_no'] + 1;
        $code = $division['contract_prefix'].$no;
        $sql = 'UPDATE '.$table_prefix.'division SET contract_no = contract_no + 1 '.
               'WHERE division_id = "'.$db->escapeSql($division_id).'" ';
        $db->executeSql($sql,$error);
        if($error !== '') throw new Exception('SERVICE_CONTRACT_CALC: Could not update division contract no');

        return $code;
    }

    //get all or part of a contract details
    public static function getContract($db,$table_prefix,$contract_id,$param = []) 
    {
        $output = [];

        if(!isset($param['get'])) $param['get'] = 'ALL';
        

        $table_contract = $table_prefix.'contract';
        $table_contract_item = $table_prefix.'contract_item';
        $table_item = $table_prefix.'service_item';
        $table_visit = $table_prefix.'contract_visit';
        $table_round = $table_prefix.'service_round';
        $table_feedback = $table_prefix.'service_feedback';
        $table_day = $table_prefix.'service_day';
        $table_visit_category = $table_prefix.'visit_category';
        $table_client = $table_prefix.'client';
        $table_contact = $table_prefix.'client_contact';
        $table_agent = $table_prefix.'agent';
        $table_division = $table_prefix.'division';
        $table_payment = $table_prefix.'pay_method';


        if($param['get'] === 'ALL' or $param['get'] === 'CONTRACT') {
            $sql = 'SELECT C.contract_id,C.type_id,C.division_id,D.name AS division,C.client_id,CL.name AS client,C.agent_id,A.name AS agent,'.
                          'CN.name AS contact, CN.position AS contact_position, CN.tel AS contact_tel, CN.cell AS contact_cell, CN.email AS contact_email, '.
                          'C.client_code,C.signed_by,C.date_signed,C.date_renew,C.date_start,C.warranty_months,C.no_assistants,'.
                          'C.notes_admin,C.notes_client,C.pay_method_id,P.name AS pay_method,'.
                          'C.no_months,C.no_visits,C.visit_day_id,DY.name AS visit_day,C.visit_time_from,C.visit_time_to,'.
                          'C.price,C.discount,C.time_estimate,C.price_visit,C.price_annual_pct '.
                   'FROM '.$table_contract.' AS C '.
                         'JOIN '.$table_division.' AS D ON(C.division_id = D.division_id) '.
                         'JOIN '.$table_client.' AS CL ON(C.client_id = CL.client_id) '.
                         'LEFT JOIN '.$table_agent.' AS A ON(C.agent_id = A.agent_id) '.
                         'LEFT JOIN '.$table_payment.' AS P ON(C.pay_method_id = P.method_id) '.
                         'LEFT JOIN '.$table_contact.' AS CN ON(C.contact_id = CN.contact_id) '.
                         'LEFT JOIN '.$table_day.' AS DY ON(C.visit_day_id = DY.day_id) '.
                   'WHERE C.contract_id = "'.$db->escapeSql($contract_id).'" ';
            $output['contract'] = $db->readSqlRecord($sql);
        }
        
        if($param['get'] === 'ALL' or $param['get'] === 'VISITS') {
            $sql = 'SELECT V.visit_id,V.category_id,C.name AS category,V.round_id,R.name AS round,V.no_assistants,V.service_no,V.invoice_no, '.
                          'V.date_booked,V.date_visit,V.notes,V.feedback_id,F.name AS feedback,F.type_id AS feedback_type '.
                    'FROM '.$table_visit.' AS V JOIN '.$table_visit_category.' AS C ON(V.category_id = C.category_id) '.
                          'JOIN '.$table_round.' AS R ON(V.round_id = R.round_id) '.
                          'JOIN '.$table_feedback.' AS F ON(V.feedback_id = F.feedback_id) '.
                    'WHERE V.contract_id = "'.$db->escapeSql($contract_id).'" ';
            $output['visits'] = $db->readSqlArray($sql);
        }

        if($param['get'] === 'ALL' or $param['get'] === 'ITEMS') {
            $sql = 'SELECT CI.item_id,I.name AS item,CI.price,CI.notes '.
                   'FROM '.$table_contract_item.' AS CI JOIN '.$table_item.' AS I ON(CI.item_id = I.item_id) '.
                   'WHERE CI.contract_id = "'.$db->escapeSql($contract_id).'" ';
            $output['items'] = $db->readSqlArray($sql);
        }

        return $output;
    }    

    public static function showContract($db,$table_prefix,$contract_id) 
    {
        $html = [];

        $param = ['get'=>'ALL'];
        $data = self::getContract($db,$table_prefix,$contract_id,$param);

        if(count($data['contract']) === 0) {
            $html['contact'] .= 'Could not get details for contract['.$contract_id.']';
        } else {
            $html['contact'] = 'Contract code <b>'.$data['contract']['client_code'].'</b> for client <b>'.$data['contract']['client'].'</b>: contact <b>'.$data['contract']['contact'].'</b> @ ';
            if($data['contract']['contact_tel']) $html['contact'] .= '<a href="tel:'.$data['contract']['contact_tel'].'">'.$data['contract']['contact_tel'].'</a> ';
            if($data['contract']['contact_cell']) $html['contact'] .=  '<a href="tel:'.$data['contract']['contact_cell'].'">'.$data['contract']['contact_cell'].'</a> ';
            if($data['contract']['contact_email']) $html['contact'] .=  '<a href="mailto:'.$data['contract']['contact_email'].'">'.$data['contract']['contact_email'].'</a> ';
        }

        if(count($data['visits']) === 0) {
            $html['visits'] .= 'No visits for contract['.$contract_id.']';
        } else {
            $arr = [];
            foreach($data['visits'] as $id => $visit) {
                $arr[$id] = ['category'=>$visit['category'],'round'=>$visit['round'],'date'=>$visit['date_start'],'assistants'=>$visit['no_assistants']];
            }
            $html['visits'] .= 'Previous visits: '.Html::arrayDumpHtml($arr);
            
        }

        if(count($data['items']) === 0) {
            $html['items'] .= 'No items linked to contract['.$contract_id.']';
        } else {
            $html['items'] .= 'Contract items: '.Html::arrayDumpHtml($data['items']);
            
        }
       

        return $html;
    }

    public static function getClient($db,$table_prefix,$client_id,$param = []) 
    {
        $client = [];

        if(!isset($param['get'])) $param['get'] = 'ALL';

        $table_client = $table_prefix.'client';
        $table_category = $table_prefix.'client_category';
        $table_contact = $table_prefix.'client_contact';
        $table_location = $table_prefix.'client_location';
        $table_location_category = $table_prefix.'location_category';

        if($param['get'] === 'ALL' or $param['get'] === 'CLIENT') {
            $sql = 'SELECT C.client_id,C.category_id,CC.name AS category,C.client_code,C.account_code,'.
                          'C.company_title,C.company_no,C.tax_reference,C.sales_code '.
                   'FROM '.$table_client.' AS C '.
                   'JOIN '.$table_category.' AS CC ON(C.category_id = CC.category_id) '.
                   'WHERE C.client_id = "'.$db->escapeSql($client_id).'" ';
            $client['client'] = $db->readSqlRecord($sql,0);
        }
        
        //NB: this will only return first ranked contact in each type_id as readsqlArray() overwrites type_id key
        if($param['get'] === 'ALL' or $param['get'] === 'CONTACT') {
            $sql = 'SELECT C.type_id,C.contact_id,C.name,C.location_id,L.name AS location,C.position,C.status,'.
                          'C.cell,C.tel,C.email,C.cell_alt,C.tel_alt,C.email_alt '.
                   'FROM '.$table_contact.' AS C '.
                   'JOIN '.$table_location.' AS L ON(C.location_id = L.location_id) '.
                   'WHERE C.client_id = "'.$db->escapeSql($client_id).'" '.
                   'ORDER BY C.type_id,C.sort DESC ';
            $client['contact'] = $db->readSqlArray($sql);
            if(!isset($client['contact']['PHYSICAL'])) {
                $client['contact']['PHYSICAL'] = ['name'=>'No on premises contact setup'];
            } 
            if(!isset($client['contact']['INVOICE'])) {
                $client['contact']['INVOICE'] = $client['contact']['PHYSICAL'];
            }
        }

        if($param['get'] === 'ALL' or $param['get'] === 'LOCATION') {
            $sql = 'SELECT L.type_id,L.location_id,L.name,C.name AS category,L.status,'.
                          'L.address,L.size,L.tel,L.email,L.map_lng,L.map_lat '.
                   'FROM '.$table_location.' AS L '.
                   'LEFT JOIN '.$table_location_category.' AS C ON(L.category_id = C.category_id) '.
                   'WHERE L.client_id = "'.$db->escapeSql($client_id).'" '.
                   'ORDER BY L.type_id,L.sort DESC ';
            $client['location'] = $db->readSqlArray($sql);
            if(!isset($client['location']['PHYSICAL'])) {
                $client['location']['PHYSICAL'] = ['name'=>'No physical location setup'];
            } 
            if(!isset($client['location']['INVOICE'])) {
                $client['location']['INVOICE'] = $client['location']['PHYSICAL'];
            }
            if(!isset($client['location'][''])) {
                $client['location']['POSTAL'] = $client['location']['PHYSICAL'];
            }
        }

        return $client;
    }

    //use to create default client location and contact data with $setup values when creating a new client 
    public static function setupClient($db,$table_prefix,$client_id,$setup = []) 
    {
        $error_tmp = '';

        $table_contact = $table_prefix.'client_contact';
        $table_location = $table_prefix.'client_location';
        $table_location_category = $table_prefix.'location_category';

        $sql = 'SELECT category_id FROM '.$table_location_category.' WHERE status <> "HIDE" ORDER BY sort LIMIT 1 ';
        $location_category_id = $db->readSqlValue($sql,0);

        $client = self::get($db,$table_prefix,'client',$client_id);

        $sql = 'SELECT count(*) FROM '.$table_location.' WHERE client_id = "'.$db->escapeSql($client_id).'" ORDER BY sort';
        $count = $db->readSqlValue($sql);
        if($count == 0) {
            $data = [];
            $data['category_id'] = $location_category_id;
            $data['client_id'] = $client_id;
            $data['name'] = 'Head office';
            $data['address'] = $setup['address_physical'];
            $data['tel'] = $setup['contact_tel'];
            $data['email'] = $setup['contact_email'];
            $data['type_id'] = 'PHYSICAL';
            $data['sort'] = '10';
            $data['status'] = 'OK';

            $location_id = $db->insertRecord($table_location,$data,$error_tmp);
            if($error_tmp !== '') throw new Exception('SERVICE_SETUP_CLIENT: Could not add default location');
                
        }

        $sql = 'SELECT COUNT(*) FROM '.$table_contact.' WHERE client_id = "'.$db->escapeSql($client_id).'" ';
        $count = $db->readSqlValue($sql);
        if($count == 0) {
            $data = [];
            $data['location_id'] = $location_id;
            $data['client_id'] = $client_id;
            $data['name'] = $setup['contact_name'];
            $data['position'] = 'Service manager';
            $data['email'] = $setup['contact_email'];
            $data['tel'] = $setup['contact_tel'];
            $data['cell'] = $setup['contact_tel'];
            $data['type_id'] = 'PHYSICAL';
            $data['sort'] = '10';
            $data['status'] = 'OK';

            $contact_id = $db->insertRecord($table_contact,$data,$error_tmp);
            if($error_tmp !== '') throw new Exception('SERVICE_SETUP_CLIENT: Could not add default contact');
        }
    }
    
    public static function roundDailyDiary($db,$table_prefix,$round_id,$user_id_tech,$status,$date_from,$date_to,$options = [],&$error)
    {
        $error = '';

        if(!isset($options['format'])) $options['format'] = 'TIME_DATE'; //'DATE_TIME' other option

        $table_visit = $table_prefix.'contract_visit';
        $table_contract = $table_prefix.'contract';
        $table_client = $table_prefix.'client';
        $table_user = TABLE_USER;
                
        $calendar = new Calendar();
                
        $sql = 'SELECT V.visit_id,V.contract_id,V.user_id_booked,V.user_id_tech,U.name AS technician, '.
                      'V.date_booked,V.date_visit,V.notes,V.status,V.time_from,V.time_to,V.status, '.
                      'C.client_code, C.client_id,CL.name AS client '.
               'FROM '.$table_visit.' AS V '.
                     'JOIN '.$table_contract.' AS C ON(V.contract_id = C.contract_id) '.
                     'JOIN '.$table_client.' AS CL ON(C.client_id = CL.client_id) '.
                     'LEFT JOIN '.$table_user.' AS U ON(V.user_id_tech = U.user_id) '.
               'WHERE C.round_id = "'.$db->escapeSql($round_id).'" AND V.date_visit >= "'.$db->escapeSql($date_from).'" AND V.date_visit <= "'.$db->escapeSql($date_to).'" ';
        if($user_id_tech != 'ALL') $sql .= ' AND V.user_id_tech = "'.$db->escapeSql($user_id_tech).'" ';
        if($status != 'ALL') $sql .= ' AND V.status = "'.$db->escapeSql($status).'" ';
        
        $entries = $db->readSqlArray($sql,false);
        if($entries == 0) $error .= 'No diary entries found over period from '.$date_from.' to '.$date_to;
        
        //if($error !== '') return false;

        $cal_options = [];
        //$cal_options['round_id'] = $round_id;
        foreach($entries as $entry) {
            $html = self::formatAppointment($entry);
            $calendar->addAppointment($entry['date_visit'],$entry['time_from'],$entry['time_to'],$html,$cal_options);
        }

        $cal_options = [];
        $html = $calendar->show($options['format'],$date_from,$date_to,$cal_options);

        return $html;
    }

    public static function formatAppointment($entry = [],$options = [])
    {
        $html = '';

        if(!isset($options['link'])) $options['link'] = true;
        if(!isset($options['tag'])) $options['tag'] = true;
        if(!isset($options['width'])) $options['width'] = 400;
        if(!isset($options['height'])) $options['height'] = 600;
        
        $str = $entry['client_code'].': '.$entry['client'].' - '.$entry['status'];
        if($entry['user_id_tech'] != 0) $str .= '('.$entry['technician'].')';

        if($options['link']) {
            if($entry['status'] === 'NEW') $mode = 'mode=edit&'; else $mode = '';  
            $href = "javascript:open_popup('diary_visit?".$mode."id=".$entry['visit_id']."',".$options['width'].",".$options['height'].")";
            $html .= '<a href="'.$href.'">'.$str.'</a>';
        } else {
            $html .= $str;
        }    
        
        return $html;
    } 
}
