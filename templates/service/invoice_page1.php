<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$text_param['class'] = 'form-control edit_input';
$list_param['class'] = 'form-control edit_input';
$date_param['class'] = 'form-control edit_input bootstrap_date';

/*
<div class="row">
    <div class="col-sm-3">Date references:</div>
    <div class="col-sm-3">
    <?php 
    $date_apply_arr = ['LAST_INVOICE'=>'Last invoice before...','VISIT_COMPLETE'=>'COMPLETED Visits on...'];
    echo Form::arrayList($date_apply_arr,'apply_date_to',$form['apply_date_to'],true,$list_param); 
    ?>
    </div>
  </div>

*/
?>

<div id="order_div">

  <p>
  <h2>Specify division contracts for invoicing:</h2>
  <br/>
  </p>
  
  <div class="row">
    <div class="col-sm-3">Invoice type:</div>
    <div class="col-sm-3">
    <?php 
    $type_arr = ['STANDARD'=>'Standard invoices','AUDIT'=>'Repeat contracts audit fee'];
    echo Form::arrayList($type_arr,'invoice_type',$form['invoice_type'],true,$list_param); 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Division:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT `division_id`, `name` FROM `'.TABLE_PREFIX.'division` WHERE `status` <> "HIDE" ORDER BY `sort`';
    echo Form::sqlList($sql,$db,'division_id',$form['division_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Contract type:</div>
    <div class="col-sm-3">
    <?php 
    $type_arr = ['SINGLE'=>'Single shot contracts','REPEAT'=>'Repeat contracts'];
    echo Form::arrayList($type_arr,'type_id',$form['type_id'],true,$list_param); 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Service round:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT `round_id`, `name` FROM `'.TABLE_PREFIX.'service_round` WHERE `status` <> "HIDE" ORDER BY `sort`';
    echo Form::sqlList($sql,$db,'round_id',$form['round_id'],$list_param) 
    ?>
    </div>
  </div>

  
  <div class="row">
    <div class="col-sm-3">Last invoiced before:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('date_last_invoice',$form['date_last_invoice'],$date_param)
    ?>
    </div>
  </div>

   <div class="row">
    <div class="col-sm-3">Client name(or part of):</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('client_name',$form['client_name'],$text_param)
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Client contract code(or part of):</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('client_code',$form['client_code'],$text_param)
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

    
  
</div>