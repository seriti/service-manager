<?php
namespace App\Service;

use Seriti\Tools\Dashboard AS DashboardTool;

class Dashboard extends DashboardTool
{
     

    //configure
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $login_user = $this->getContainer('user'); 

        //(block_id,col,row,title)
        $this->addBlock('CLIENT',1,1,'Manage clients');
        $this->addItem('CLIENT','Add a new Client',['link'=>"client?mode=add"]);
        $this->addItem('CLIENT','Add a new SINGLE Contract',['link'=>"contract_single?mode=add"]);
        $this->addItem('CLIENT','Add a new REPEAT Contract',['link'=>"contract_repeat?mode=add"]);

        $this->addBlock('WIZARD',1,2,'Process wizards');
        $this->addItem('WIZARD','Diarise client visits using contract details',['link'=>"diary_wizard"]);
        $this->addItem('WIZARD','Invoice multiple contracts',['link'=>"invoice_wizard"]);
        //$this->addItem('WIZARD','Invoice completed visits',['link'=>"invoice_wizard"]);
                        
        if($login_user->getAccessLevel() === 'GOD') {
            $this->addBlock('CONFIG',1,3,'Module Configuration');
            $this->addItem('CONFIG','Setup Invoices',['link'=>'invoice_setup','icon'=>'setup']);
            $this->addItem('CONFIG','Setup Database',['link'=>'setup_data','icon'=>'setup']);
        }    
        
    }

}
