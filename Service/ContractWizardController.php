<?php
namespace App\Service;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Service\ContractWizard;

class ContractWizardController
{
    protected $container;
        

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;
        $cache = $this->container->cache;

        $user_specific = true;
        $cache_name = 'contract_wizard';
        $cache->setCache($cache_name,$user_specific);

        $wizard_template = new Template(BASE_TEMPLATE);
        
        $wizard = new ContractWizard($db,$this->container,$cache,$wizard_template);
        $wizard->setup();        

        $html = $wizard->process();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Contract wizard';
        //$template['javascript'] = $wizard->getJavascript();

        return $this->container->view->render($response,'admin.php',$template);
    }
}