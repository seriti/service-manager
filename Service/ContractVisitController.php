<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\ContractVisit;

class ContractVisitController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'contract_visit';
        $table = new ContractVisit($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        //$template['title'] = MODULE_LOGO.'All Contract visits';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
