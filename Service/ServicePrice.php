<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ServicePrice extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Service price','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'division','key'=>'division_id','child_col'=>'division_id',
                            'show_sql'=>'SELECT CONCAT("Division: ",`name`) FROM `'.TABLE_PREFIX.'division` WHERE `division_id` = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'price_id','type'=>'INTEGER','title'=>'price ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item',
                            'join'=>'CONCAT(I.`name`,": ",U.`name`) FROM `'.TABLE_PREFIX.'service_item` AS I JOIN `'.TABLE_PREFIX.'item_units` AS U USING(`units_id`) WHERE I.`item_id`']);
        $this->addTableCol(['id'=>'location_category_id','type'=>'INTEGER','title'=>'Location category',
                            'join'=>'`name` FROM `'.TABLE_PREFIX.'location_category` WHERE `category_id`']);
        $this->addTableCol(['id'=>'item_quantity','type'=>'INTEGER','title'=>'Item quantity','new'=>1]);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price']);

        $this->addSql('JOIN','JOIN `'.TABLE_PREFIX.'service_item` AS I ON(T.`item_id` = I.`item_id`)');
        $this->addSql('JOIN','JOIN `'.TABLE_PREFIX.'division` AS D ON(I.`division_id` = D.`division_id`)');

        $this->addSortOrder('D.`sort`,I.`sort` ','Division then Item','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['item_id','location_category_id','price'],['rows'=>2]);

        $this->addSelect('item_id','SELECT `item_id`, `name` FROM `'.TABLE_PREFIX.'service_item` ORDER BY `sort`');
        $this->addSelect('location_category_id','SELECT `category_id`, `name` FROM `'.TABLE_PREFIX.'location_category` ORDER BY `sort`');

    }

    protected function beforeProcess($id = 0) 
    {                       
        $this->addSelect('item_id','SELECT `item_id`,`name` FROM `'.TABLE_PREFIX.'service_item` WHERE `division_id` = "'.$this->master['key_val'].'" ORDER BY `name`');
    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
