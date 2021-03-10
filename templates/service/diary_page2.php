<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;
use Seriti\Tools\Date;

$check_param['class'] = 'form-control edit_input';
$text_param['class'] = 'form-control edit_input';
$list_param['class'] = 'form-control edit_input input-small';
$time_param['class'] = 'form-control edit_input input-small';
$date_param['class'] = 'form-control edit_input bootstrap_date input-small';

$visit_days = $data['visit_days'];


?>

<div id="visit_div">
  
  <div class="row">
    <div class="col-sm-6">
        <?php  
        echo '<strong>'.$form['type_id'].'</strong> contracts for division: <strong>'.$data['division']['name'].'</strong> & round: <strong>'.$data['round']['name'].'</strong>';
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
         '<tr><th>Contract ID</th><th>Client</th><th>Code</th><th>Type</th><th>Last visit</th><th>Category</th><th>Next visit</th><th>@Time</th><th>Est. minutes</th>'.
         '<th>No. assistants</th><th>Notes</th><th>Create diary entry</th><th>Del.</th></tr>';
    
    foreach($data['contracts'] as $id => $contract) {
        $name_cat = 'cat_'.$id;
        $name_date = 'date_'.$id;
        $name_time = 'time_'.$id;
        $name_minutes = 'minutes_'.$id;
        $name_create = 'create_'.$id;
        $name_assist = 'assist_'.$id;
        $name_notes = 'notes_'.$id;

        $sql_cat = 'SELECT category_id, name FROM '.TABLE_PREFIX.'visit_category ORDER BY sort';

        echo '<tr>'.
             '<td>'.$id.'</td>'.
             '<td>'.$contract['client'].'</td>'.
             '<td>'.$contract['client_code'].'</td>'.
             '<td>'.$contract['type_id'].'</td>'.
             '<td>'.$contract['date_last'].'</td>'.
             '<td>'.Form::sqlList($sql_cat,$this->db,$name_cat,$contract['new_cat'],$list_param) .'</td>'.
             '<td>'.Form::textInput($name_date,$contract['new_date'],$date_param).'</td>'.
             '<td>'.Form::textInput($name_time,$contract['new_time'],$time_param).'</td>'.
             '<td>'.Form::textInput($name_minutes,$contract['new_minutes'],$time_param).'</td>'.
             '<td>'.Form::textInput($name_assist,$contract['no_assist'],$time_param).'</td>'.
             '<td>'.Form::textAreaInput($name_notes,$contract['notes'],10,2,$text_param).'</td>'.
             '<td>'.Form::checkBox($name_create,'YES',$contract['new_create'],$check_param).'</td>'.
             '<td><a href="#" onclick="delete_row(this)"><img src="/images/cross.png"></a></td>'.
             '</tr>';
    }
    echo '</table>';
    

    ?>
    </div>
  </div>
  
  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Validate and preview preliminary diary entries" class="btn btn-primary"></div>
  </div>  

</div>

<script language="javascript">

function delete_row(link) {
    var row = link.parentNode.parentNode;
    row.parentNode.removeChild(row);
};


</script>  