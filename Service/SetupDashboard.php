<?php
namespace App\Service;

use Seriti\Tools\Form;
use Seriti\Tools\Dashboard AS DashboardTool;

class SetupDashboard extends DashboardTool
{
    protected $labels = MODULE_RESERVE['labels'];

    //configure
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $login_user = $this->getContainer('user'); 
       
                
        $this->addBlock('GENERAL',1,1,'General setup');
        $this->addItem('GENERAL','Business divisions & service items & pricing',['link'=>'division']);
        $this->addItem('GENERAL','Client categories',['link'=>'client_category']); 
        $this->addItem('GENERAL','Client Address/Location categories',['link'=>'location_category']); 
        $this->addItem('GENERAL','Accounting contract codes',['link'=>'account_code']); 
          
        

        $this->addBlock('CONTRACT',2,1,'Contract agreement setup');
        $this->addItem('CONTRACT','Payment methods',['link'=>"pay_method"]);    
        $this->addItem('CONTRACT','Agents',['link'=>'agent']);  
        
        $this->addBlock('SERVICE',1,2,'Service setup');
        $this->addItem('SERVICE','Service item units',['link'=>'item_units']);
        $this->addItem('SERVICE','Service categories',['link'=>'visit_category']);
        $this->addItem('SERVICE','Errand categories',['link'=>'errand_category']);
        $this->addItem('SERVICE','Service feedback',['link'=>'service_feedback']);
        $this->addItem('SERVICE','Service days',['link'=>'service_day']);
        $this->addItem('SERVICE','Service rounds',['link'=>'service_round']);
        

        if($login_user->getAccessLevel() === 'GOD') {
            /*
            $this->addBlock('USER',2,1,'User setup');
            $this->addItem('USER','NON-admin user settings',['link'=>"user_extend?mode=list"]);
            $this->addItem('USER','Agent setup',['link'=>"agent?mode=list"]);
            */
        }    
        
    }
}

?>