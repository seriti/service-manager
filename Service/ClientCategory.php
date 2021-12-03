<?php
namespace App\Service;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ClientCategory extends Table
{
    protected $access_rank = 100;

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client category','row_name_plural'=>'Client categories','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $config = $this->getContainer('config');

        //$this->user_access_level and $this->user_id set in parent::setup() above
        if(isset(ACCESS_RANK[$this->user_access_level])) $this->access_rank = ACCESS_RANK[$this->user_access_level];

        $this->addTableCol(['id'=>'category_id','type'=>'INTEGER','title'=>'category ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'access','type'=>'STRING','title'=>'Access','new'=>'ADMIN',
                            'hint'=>'(GOD can do anything!<br/>
                                     ADMIN allows users to add, and delete most data.<br/>
                                     USER allows users to add and edit but not delete data.<br/>
                                     VIEW allows users to see anything but not to modify or add any data!']);
        //$this->addTableCol(['id'=>'access_level','type'=>'INTEGER','title'=>'Access level']);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort order']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSql('WHERE','T.`access_level` >= "'.$this->access_rank.'" ');

        $this->addSortOrder('T.`sort`','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['category_id','name','access','access_level','sort','status'],['rows'=>1]);

        $this->addSelect('access',['list'=>$config->get('user','access'),'list_assoc'=>false]);


        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }


    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'access') {
            if($value === 'GOD') {
                $value = 'GOD only';
            } else {
                $value .= ' & higher';    
            }    
        }    
    } 

    protected function afterUpdate($id,$edit_type,$form) {
        $sql = 'UPDATE `'.$this->table.'` SET `access_level` = "'.ACCESS_RANK[$form['access']].'" '.
               'WHERE `'.$this->key['id'].'` = "'.$this->db->escapeSql($id).'" ';

        $this->db->executeSql($sql,$error_tmp);  
         
    }
    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
