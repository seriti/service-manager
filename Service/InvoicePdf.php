<?php
namespace App\Service;

use Seriti\Tools\Pdf;
use Seriti\Tools\Date;


use App\Service\Helpers;

class InvoicePdf extends Pdf
{
    //all margins within page borders
    protected $invoice_margin = 10;
    //all margins within block borders 
    protected $block_margin = 2; //4
    //space between blocks
    protected $block_padding = 4;
    
    //blocks contain multiple line text
    protected $text_block = [];
    //elements contain single text value
    protected $text_element = [];
    

    public function addTextBlock($name,$content = []) 
    { 
        $this->text_block[$name] = $content;
    }

    public function addTextElement($name,$value = []) 
    { 
        $this->text_element[$name] = $value;
    }
    
    public function header() 
    {
        $this->SetFont($this->font_face);
        
        //draw all borders first
        $this->changeFont('H2');
        $this->SetLineWidth(.1);
        $this->SetDrawColor(0,0,0);

        $block_w = ($this->w - ($this->invoice_margin * 2) - $this->block_padding) / 2 ;
        $block_h = 40;
        $title_h = 8; //10
        $row_h = 6;
        $info_h = 5; //6

        //Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
        $image_w = 0;
        $image_h = 0;
        if(!isset($this->bg_image[5]) or $this->bg_image[5] !== 'YES') {
            //custom logo setup for pdf
            $custom_w = $this->bg_image[3];
            $custom_h = $this->bg_image[4];
            //modify to fit
            $image_x = $this->invoice_margin + 1;
            $image_y = $this->invoice_margin + 1;
            $image_h = $title_h + 2;
            $image_w = ($custom_w / $custom_h) * $image_h;

            $this->Image($this->bg_image[0],$image_x,$image_y,$image_w,$image_h);
        } 

        //top left, business details
        $pos_x = $this->invoice_margin;
        $pos_y = $this->invoice_margin;
        $width = $block_w;
        $height = $block_h;
        $this->Rect($pos_x,$pos_y,$width,$height);
        //topright, document details
        $pos_x = $this->invoice_margin + $block_w + $this->block_margin;
        $pos_y = $this->invoice_margin;
        $width = $block_w;
        $height = $block_h;
        $this->Rect($pos_x,$pos_y,$width,$height);
        //below top left, client details
        $pos_x = $this->invoice_margin;
        $pos_y = $this->invoice_margin + $block_h + $this->block_margin;
        $width = $block_w;
        $height = $block_h;
        $this->Rect($pos_x,$pos_y,$width,$height);
        //below top left, client deliver address
        $pos_x = $this->invoice_margin + $block_w + $this->block_margin;
        $pos_y = $this->invoice_margin + $block_h + $this->block_margin;
        $width = $block_w;
        $height = $block_h;
        $this->Rect($pos_x,$pos_y,$width,$height);

        //add business details content
        $shift_x = $this->block_margin;
        $this->SetY($this->invoice_margin + $this->block_margin);
        $this->changeFont('H2');
        $this->Cell($shift_x + $image_w);
        $this->Cell(0,$title_h,$this->text_element['business_title'],0,0,'L',0);
        $this->Ln($title_h);
        $this->changeFont('TEXT');
        //left half text block
        $temp_y = $this->getY();
        $this->Cell($shift_x);
        $this->MultiCell($block_w/2,$info_h,$this->text_block['business_address'],0,'L',0);
        //right half text block
        $this->SetY($temp_y);
        $this->Cell($block_w/2);
        $this->MultiCell($block_w/2,$info_h,$this->text_block['business_contact'],0,'L',0);
         
        //add document detals
        $shift_x = $block_w + $this->block_padding + $this->block_margin;
        $this->SetY($this->invoice_margin + $this->block_margin);
        $this->changeFont('H2');
        $this->Cell($shift_x);
        $this->Cell(0,$title_h,$this->text_element['doc_name'],0,0,'L',0); 
        $this->Ln($title_h);
        $this->changeFont('TEXT');
        $this->Cell($shift_x);
        $cell_w = $block_w/2 - $this->block_margin*2;
        $this->Cell($cell_w,$row_h,'Date',0,0,'L',0); 
        $this->Cell($cell_w,$row_h,$this->text_element['doc_date'],0,0,'R',0); 
        $this->Ln($row_h);
        $this->Cell($shift_x);
        $this->Cell($cell_w,$row_h,'Page',0,0,'L',0); 
        $this->Cell($cell_w,$row_h,$this->PageNo().' of {nb}',0,0,'R',0); 
        $this->Ln($row_h);
        $this->Cell($shift_x);
        $this->Cell($cell_w,$row_h,'Document No.',0,0,'L',0); 
        $this->Cell($cell_w,$row_h,$this->text_element['doc_no'],0,0,'R',0); 
        
        //add client details
        $shift_x = $this->block_margin;
        $this->SetY($this->invoice_margin + $block_h + $this->block_padding + $this->block_margin);
        $this->changeFont('TEXT');
        $this->Cell($shift_x);
        $this->MultiCell($block_w/2,$info_h,$this->text_block['client_detail'],0,'L',0);

        //add client deliver to details
        $shift_x = $block_w + $this->block_padding + $this->block_margin;
        $this->SetY($this->invoice_margin + $block_h + $this->block_padding + $this->block_margin);
        $this->changeFont('TEXT');
        $this->Cell($shift_x);
        $this->MultiCell($block_w/2,$info_h,$this->text_block['client_deliver'],0,'L',0);

        //add account details
        $pos_x = $this->getX();
        $this->SetY($this->invoice_margin + $block_h + $this->block_padding + $block_h + $this->block_margin);
        $shift_x = $this->block_margin;
        $this->Cell($shift_x,$row_h,'','B');
        $this->Cell(30,$row_h,'Account','B',0,'L',0); 
        $this->Cell(30,$row_h,'Your reference','B',0,'L',0); 
        $this->Cell(30,$row_h,'Tax exempt','B',0,'L',0); 
        $this->Cell(30,$row_h,'Tax reference','B',0,'L',0); 
        $this->Cell(0,$row_h,'Sales code','B',0,'L',0); 
        $this->Ln($row_h);
        $this->Cell($shift_x);
        $this->Cell(30,$row_h,$this->text_element['acc_no'],0,0,'L',0); 
        $this->Cell(30,$row_h,$this->text_element['acc_ref'],0,0,'L',0); 
        $this->Cell(30,$row_h,$this->text_element['acc_tax_exempt'],0,0,'L',0); 
        $this->Cell(30,$row_h,$this->text_element['acc_tax_ref'],0,0,'L',0); 
        $this->Cell(0,$row_h,$this->text_element['acc_sales_code'],0,0,'L',0); 
        $this->Ln($row_h);
        //add frame around account details
        $pos_y = $this->getY() - $row_h*2;
        $width = $block_w*2 + $this->block_padding ;
        $height = $row_h*2;
        $this->Rect($pos_x,$pos_y,$width,$height);
        
        //can ignore this and Y will be after last line feed.
        $this->SetY($this->page_margin[0]);

        
    }

    //Page footer (called automatically in FPDF but do nothing unless extended  like below)
    //NB: must be PUBLIC as defined public in FPDF 
    public function footer() 
    {
        //$this->changeFont('H2');
        $this->SetLineWidth(.1);
        $this->SetDrawColor(0,0,0);

        $block_w = ($this->w - ($this->invoice_margin * 2) - $this->block_padding) / 2 ;
        $block_h = 30;
        $total_h = 6;
        $row_h = 6;

        $this->SetY(-($this->invoice_margin + $block_h));
        $start_y = $this->getY();

        //bottom left, reception and banking details
        $pos_x = $this->invoice_margin;
        $pos_y = $start_y;
        $width = $block_w;
        $height = $block_h;
        $this->Rect($pos_x,$pos_y,$width,$height);
        //bottom right, totals
        $pos_x = $this->invoice_margin + $block_w + $this->block_margin;
        $pos_y = $start_y;
        $width = $block_w;
        $height = $block_h;
        $this->Rect($pos_x,$pos_y,$width,$height);
        

        //add reception details and banking info content
        $this->SetY($start_y);
        $shift_x = $this->block_margin;
        $this->changeFont('TEXT');
        $this->Cell($shift_x);
        $this->MultiCell($block_w,$row_h,$this->text_block['total_info'],0,'L',0);
        
        //add totals block info
        $this->SetY($start_y);
        $shift_x = $block_w + $this->block_padding + $this->block_margin;
        $cell_w = $block_w/2 - $this->block_margin;
        $this->changeFont('TEXT');
        $this->Cell($shift_x);
        $this->Cell($cell_w,$total_h,'Sub total',0,0,'L',0); 
        $this->Cell($cell_w,$total_h,$this->text_element['total_sub'],0,0,'R',0); 
        $this->Ln($total_h);
        $this->Cell($shift_x);
        $this->Cell($cell_w,$total_h,'Discount',0,0,'L',0); 
        $this->Cell($cell_w,$total_h,$this->text_element['total_discount'],0,0,'R',0); 
        $this->Ln($total_h);
        $this->Cell($shift_x);
        $this->Cell($cell_w,$total_h,'Amount Excl. Tax',0,0,'L',0); 
        $this->Cell($cell_w,$total_h,$this->text_element['total_ex_tax'],0,0,'R',0); 
        $this->Ln($total_h);
        $this->Cell($shift_x);
        $this->Cell($cell_w,$total_h,'Tax',0,0,'L',0); 
        $this->Cell($cell_w,$total_h,$this->text_element['total_tax'],0,0,'R',0); 
        $this->Ln($total_h);
        $this->changeFont('H2');
        $this->Cell($shift_x);
        $this->Cell($cell_w,$total_h,'Total',0,0,'L',0); 
        $this->Cell($cell_w,$total_h,$this->text_element['total'],0,0,'R',0); 
        $this->Ln($total_h);
        
        
    }

}