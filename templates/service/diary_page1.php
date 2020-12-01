<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;

$list_param['class'] = 'form-control edit_input';
$date_param['class'] = 'form-control edit_input bootstrap_date';
?>

<div id="order_div">

  <p>
  <h2>Review contract diary for:</h2>
  <br/>
  </p>
  

  <div class="row">
    <div class="col-sm-3">Division:</div>
    <div class="col-sm-3">
    <?php 
    $sql = 'SELECT division_id, name FROM '.TABLE_PREFIX.'division WHERE status <> "HIDE" ORDER BY sort';
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
    $sql = 'SELECT round_id, name FROM '.TABLE_PREFIX.'service_round WHERE status <> "HIDE" ORDER BY sort';
    echo Form::sqlList($sql,$db,'round_id',$form['round_id'],$list_param) 
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">Last visited before:</div>
    <div class="col-sm-3">
    <?php 
    echo Form::textInput('date_last_visit',$form['date_last_visit'],$date_param)
    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Proceed" class="btn btn-primary"></div>
  </div>  

    
  
</div>