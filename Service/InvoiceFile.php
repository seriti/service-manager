<?php 
namespace App\Service;

use Seriti\Tools\Upload;

class InvoiceFile extends Upload 
{
  //configure
    public function setup($param = []) 
    {
        $id_prefix = 'INV'; 

        $param = ['row_name'=>'Invoice document',
                  'pop_up'=>true,
                  'update_calling_page'=>true,
                  'prefix'=>$id_prefix,//will prefix file_name if used, but file_id.ext is unique 
                  'upload_location'=>$id_prefix]; 
        parent::setup($param);

        $param=[];
        $param['table']     = TABLE_PREFIX.'contract_invoice';
        $param['key']       = 'invoice_id';
        $param['label']     = 'invoice_no';
        $param['child_col'] = 'location_id';
        $param['child_prefix'] = $id_prefix;
        $param['show_sql'] = 'SELECT CONCAT("Invoice ID[",invoice_id,"] ",date) FROM '.TABLE_PREFIX.'contract_invoice WHERE invoice_id = "{KEY_VAL}" ';
        $this->setupMaster($param);

        //$this->addAction('delete');

        //$access['read_only'] = true;
        //$this->modifyAccess($access);
    }
}
?>
