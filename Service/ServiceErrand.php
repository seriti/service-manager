<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ServiceErrand extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Service errand','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'errand_id','type'=>'INTEGER','title'=>'errand ID','key'=>true]);
        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'Client id','join'=>'`name` FROM `'.TABLE_PREFIX.'client` WHERE `client_id`']);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location id','join'=>'`name` FROM `'.TABLE_PREFIX.'location` WHERE `location_id`']);
        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'Category id','join'=>'`name` FROM `'.TABLE_PREFIX.'category` WHERE `category_id`']);
        $this->addTableCol(['id'=>'round_id','type'=>'INTEGER','title'=>'Round id','join'=>'`name` FROM `'.TABLE_PREFIX.'round` WHERE `round_id`']);
        $this->addTableCol(['id'=>'date','type'=>'DATE','title'=>'Date','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'time_arrive','type'=>'TIME','title'=>'Time arrive']);
        $this->addTableCol(['id'=>'time_leave','type'=>'TIME','title'=>'Time leave']);
        $this->addTableCol(['id'=>'notes','type'=>'TEXT','title'=>'Notes','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.`errand_id` DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['errand_id','client_id','location_id','category_id','round_id','date','time_arrive','time_leave','notes','status'],['rows'=>2]);

        $this->addSelect('client_id','SELECT `client_id`, `name` FROM `'.TABLE_PREFIX.'client` WHERE `status` <> "HIDE" ORDER BY `name`');
        $this->addSelect('location_id','SELECT `location_id`, `name` FROM `'.TABLE_PREFIX.'location` ORDER BY `name`');
        $this->addSelect('category_id','SELECT `category_id`, `name` FROM `'.TABLE_PREFIX.'category` ORDER BY `name`');
        $this->addSelect('round_id','SELECT `round_id`, `name` FROM `'.TABLE_PREFIX.'round` ORDER BY `name`');
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
