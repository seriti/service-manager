<?php
use Seriti\Tools\Date;

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
    echo '<h2>Successfully created <strong>'.$data['create_no'].'</strong> preliminary diary visit entries. Please view diary and confirm bookings with clients using contact details provided there.</h2>';     

    ?>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-12">
     <a href="diary_wizard"><button class="btn btn-primary">Restart wizard for another divison and service round</button></a>
    </div>
  </div>
  