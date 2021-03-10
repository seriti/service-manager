<?php
use Seriti\Tools\Form;
use Seriti\Tools\Html;
use Seriti\Tools\Date;

$sql = 'SELECT category_id, name FROM '.TABLE_PREFIX.'visit_category ORDER BY sort';
$category = $this->db->readSqlList($sql);

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
         '<tr><th>Contract</th><th>Category</th><th>Visit on</th><th>From</th><th>To</th><th>No. assistants</th><th>Notes</th></tr>';
    
    foreach($data['contracts'] as $id => $contract) {
        if($contract['new_create']) {
          echo '<tr>'.
               '<td>'.$id.': '.$contract['client_code'].'</td>'.
               '<td>'.$category[$contract['new_cat']].'</td>'.
               '<td>'.Date::formatDate($contract['new_date']).'</td>'.
               '<td>'.$contract['new_time'].'</td>'.
               '<td>'.Date::incrementTime($contract['new_time'],$contract['new_minutes']).'</td>'.
               '<td>'.$contract['no_assist'].'</td>'.
               '<td>'.$contract['notes'].'</td>'.
               '</tr>';

        }

        
    }
    echo '</table>';
    

    ?>
    </div>
  </div>
  
  <div class="row">
    <div class="col-sm-6"><input type="submit" name="Submit" value="Create diary visit entries" class="btn btn-primary"></div>
  </div>  

</div>
