<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\InvoiceSetup;

class InvoiceSetupController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $module = $this->container->config->get('module','service');  
        $setup = new InvoiceSetup($this->container->mysql,$this->container,$module);

        $setup->setup();
        $html = $setup->processSetup();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Invoice settings';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}