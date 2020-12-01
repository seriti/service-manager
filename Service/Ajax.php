<?php
namespace App\Service;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Form;
use Seriti\Tools\Secure;

use App\Service\Helpers;


class Ajax
{
    protected $container;
    protected $db;
    protected $user;

    protected $debug = false;
    //Class accessed outside /App/Auction so cannot use TABLE_PREFIX constant
    protected $table_prefix = '';
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $this->container->mysql;
        $this->user = $this->container->user;

        //Class may be accessed outside /App/Service so cannot use TABLE_PREFIX constant
        $module = $this->container->config->get('module','service');
        $this->table_prefix = $module['table_prefix'];

        if(defined('\Seriti\Tools\DEBUG')) $this->debug = \Seriti\Tools\DEBUG;
    }


    public function __invoke($request, $response, $args)
    {
        $mode = '';
        $output = '';

        if(isset($_GET['mode'])) $mode = Secure::clean('basic',$_GET['mode']);

        if($mode === 'client_locations') $output = $this->getClientLocations($_POST);
        if($mode === 'client_contacts') $output = $this->getClientContacts($_POST);
        if($mode === 'contract_contacts') $output = $this->getContractContacts($_POST);

        return $output;
    }

    protected function getClientLocations($form)
    {
        $error = '';
               
        $client_id = Secure::clean('alpha',$form['client_id']);
        $sql = 'SELECT location_id, name FROM '.$this->table_prefix.'client_location '.
               'WHERE client_id = "'.$this->db->escapeSql($client_id).'" AND status <> "HIDE" ORDER BY sort';    
        
        $locations = $this->db->readSqlList($sql);    
        if($locations == 0) $error = 'No locations found for client ID['.$client_id.']';
        
        if($error !== '') {
            return 'ERROR: '.$error;
        } else {
            return json_encode($locations);
        }
    }

    protected function getClientContacts($form)
    {
        $error = '';
        $html = '';
       
        $client_id = Secure::clean('alpha',$form['client_id']);
        $sql = 'SELECT contact_id, name FROM '.$this->table_prefix.'client_contact '.
               'WHERE client_id = "'.$this->db->escapeSql($client_id).'" AND status <> "HIDE" ORDER BY sort';    
        
        $contacts = $this->db->readSqlList($sql);    
        if($contacts == 0) $error = 'No contacts found for client ID['.$client_id.']';
        
        if($error !== '') {
            return 'ERROR: '.$error;
        } else {
            return json_encode($contacts);
        }
    }

    protected function getContractContacts($form)
    {
        $error = '';
        $html = '';
       
        $contract_id = Secure::clean('alpha',$form['contract_id']);
        $contract = Helpers::get($this->db,$this->table_prefix,'contract',$contract_id);

        if($contract == 0) {
            $error .= 'Invalid contract ID['.$contract_id.']';
        } else {
            $sql = 'SELECT contact_id, name FROM '.$this->table_prefix.'client_contact '.
                   'WHERE client_id = "'.$this->db->escapeSql($contract['client_id']).'" AND status <> "HIDE" ORDER BY sort';    
            
            $contacts = $this->db->readSqlList($sql);    
            if($contacts == 0) $error .= 'No contacts found for contract client ID['.$contract['client_id'].']';
        }    
        
        if($error !== '') {
            return 'ERROR: '.$error;
        } else {
            return json_encode($contacts);
        }
        
    }
}