<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;
use Seriti\Tools\Date;

$text_param['class'] = 'form-control edit_input';
$check_param['class'] = 'form-control edit_input';
$list_param['class'] = 'form-control edit_input input-small';
$value_param['class'] = 'form-control edit_input input-small';
$date_param['class'] = 'form-control edit_input bootstrap_date input-small';

$visit_days = $data['visit_days'];


?>

<div id="visit_div">
  
  <div class="row">
    <div class="col-sm-6">
        <?php  
        echo '<strong>'.$form['type_id'].'</strong> Invoice contracts for division: <strong>'.$data['division']['name'].'</strong> & round: <strong>'.$data['round']['name'].'</strong>';
        ?>
    </div>
  </div>

  <br/>
  <div class="row">
    <div class="col-sm-12">
    <?php 
    $item_param = [];
    $item_param['class'] = 'form-control';
    
    echo '<table id="item_table" class="table  table-striped table-bordered table-hover table-condensed">'.
         '<tr><th>Contract ID</th><th>Client</th><th>Code</th><th>Type</th><th>Last invoice</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th><th>Notes</th><th>Invoice Action</th><th>Process invoice</th><th>Del.</th></tr>';
    
    $action = ['NONE'=>'Only create invoice PDF','EMAIL'=>'Create invoice PDF & email to client'];
    foreach($data['contracts'] as $id => $contract) {
        
        $name_note = 'note_'.$id;
        $name_create = 'create_'.$id;
        $name_action = 'action_'.$id;
        
        $item_link = '<a href="Javascript:open_popup(\'invoice_wizard_item?id='.$id.'\',600,600)">Invoice items</a>';

        echo '<tr>'.
             '<td>'.$id.': '.$item_link.'</td>'.
             '<td>'.$contract['client'].'</td>'.
             '<td>'.$contract['client_code'].'</td>'.
             '<td>'.$contract['type_id'].'</td>'.
             '<td>'.$contract['inv_date_last'].'</td>'.
             '<td>'.$contract['inv_subtotal'].'</td>'.
             '<td>'.$contract['inv_discount'].'</td>'.
             '<td>'.$contract['inv_tax'].'</td>'.
             '<td>'.$contract['inv_total'].'</td>'.
             '<td>'.Form::textAreaInput($name_note,$contract['inv_note'],10,2,$text_param).'</td>'.
             '<td>'.Form::arrayList($action,$name_action,$contract['inv_action'],true,$list_param).'</td>'.
             '<td>'.Form::checkBox($name_create,'YES',$contract['inv_create'],$check_param).'</td>'.
             '<td><a href="#" onclick="delete_row(this)"><img src="/images/cross.png"></a></td>'.
             '</tr>';
    }
    echo '</table>';
    

    ?>
    </div>
  </div>
  
  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Process selected invoices" class="btn btn-primary"></div>
  </div>  

</div>

<script language="javascript">

function delete_row(link) {
    var row = link.parentNode.parentNode;
    row.parentNode.removeChild(row);
};


</script>  