<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\ServiceSetup;

class ServiceSetupController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $module = $this->container->config->get('module','service');  
        $setup = new ServiceSetup($this->container->mysql,$this->container,$module);

        $setup->setup();
        $html = $setup->processSetup();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Service manager settings';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}