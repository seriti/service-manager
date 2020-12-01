<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;


class InvoiceItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Invoice item','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'contract_invoice','key'=>'invoice_id','child_col'=>'invoice_id',
                            'show_sql'=>'SELECT CONCAT("Invoice ID[",invoice_id,"] ",date) FROM '.TABLE_PREFIX.'contract_invoice WHERE invoice_id = "{KEY_VAL}" ']);

        $this->modifyAccess(['read_only'=>true]);

        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'item_code','type'=>'STRING','title'=>'Item code']);
        $this->addTableCol(['id'=>'item_desc','type'=>'STRING','title'=>'Item description']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','title'=>'Quantity']);
        $this->addTableCol(['id'=>'units','type'=>'STRING','title'=>'Units']);
        $this->addTableCol(['id'=>'unit_price','type'=>'DECIMAL','title'=>'Unit Price']);
        $this->addTableCol(['id'=>'discount','type'=>'DECIMAL','title'=>'Discount']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);
        
        //$this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        //$this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['item_code','item_desc'],['rows'=>1]);
    }
}
