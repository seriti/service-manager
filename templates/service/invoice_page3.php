<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;
use Seriti\Tools\Date;

$invoice_type = $data['invoice_type'];

?>

<div id="visit_div">
  
  <div class="row">
    <div class="col-sm-6">
        <?php  
        echo '<strong>'.$form['type_id'].'</strong> Invoice contracts PROCESSED for division: <strong>'.$data['division']['name'].'</strong> & round: <strong>'.$data['round']['name'].'</strong>';
        ?>
        <h2><a href="invoice_wizard"><button class="btn btn-primary">Process invoices for another division or round</button></a></h2>
    </div>
  </div>

  <br/>
  <div class="row">
    <div class="col-sm-12">
    <?php 
    $item_param = [];
    $item_param['class'] = 'form-control';
    
    echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
         '<tr><th>Contract ID</th><th>Code</th><th>Type</th><th>Last invoice</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th><th>Notes</th><th>Process result</th></tr>';
    
    $action = ['NONE'=>'Only create invoice trecord','EMAIL'=>'Create invoice PDF & email to client'];
    foreach($data['contracts'] as $id => $contract) {
        
        if($contract['inv_create'] == true) {
            $link_param = 'id='.$id.'&type='.$invoice_type;
            $item_link = '<a href="Javascript:open_popup(\'invoice_wizard_item?'.$link_param.'\',600,600)">Invoice items</a>';

            echo '<tr>'.
                 '<td>'.$id.': '.$item_link.'</td>'.
                 '<td>'.$contract['client_code'].'</td>'.
                 '<td>'.$contract['type_id'].'</td>'.
                 '<td>'.$contract['inv_date_last'].'</td>'.
                 '<td>'.$contract['inv_subtotal'].'</td>'.
                 '<td>'.$contract['inv_discount'].'</td>'.
                 '<td>'.$contract['inv_tax'].'</td>'.
                 '<td>'.$contract['inv_total'].'</td>'.
                 '<td>'.nl2br($contract['inv_note']).'</td>'.
                 '<td>'.$contract['inv_message'].'</td>'.
                 '</tr>';    
        }
        
        
    }
    echo '</table>';
    

    ?>
    </div>
  </div>

</div>

