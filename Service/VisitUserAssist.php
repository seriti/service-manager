<?php
namespace App\Service;

use Seriti\Tools\TABLE_USER;
use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class VisitUserAssist extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Visit user assist','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'contract_visit','key'=>'visit_id','child_col'=>'visit_id',
                            'show_sql'=>'SELECT CONCAT("Visit ID[",V.visit_id,"] Contract[",C.client_code,"] on ",V.date_visit) FROM '.
                                        TABLE_PREFIX.'contract_visit AS V JOIN '.TABLE_PREFIX.'contract AS C ON(V.contract_id = C.contract_id) WHERE V.visit_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'assist_id','type'=>'INTEGER','title'=>'assist ID','key'=>true,'key_auto'=>true,'list'=>false]);
        //$this->addTableCol(['id'=>'visit_id','type'=>'INTEGER','title'=>'Visit id','join'=>'name FROM '.TABLE_PREFIX.'contract_visit WHERE visit_id']);
        $this->addTableCol(['id'=>'user_id','type'=>'INTEGER','title'=>'User','join'=>'name FROM '.TABLE_USER.' WHERE user_id']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);


        $this->addSortOrder('T.assist_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['assist_id','visit_id','user_id','note'],['rows'=>1]);

        //$this->addSelect('visit_id','SELECT visit_id, name FROM '.TABLE_PREFIX.'contract_visit ORDER BY name');
        $this->addSelect('user_id','SELECT user_id, name FROM '.TABLE_USER.' WHERE zone <> "PUBLIC" AND status <> "HIDE" ORDER BY name');

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
