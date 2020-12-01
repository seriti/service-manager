<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\Contract;

class ContractRepeatController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'contract';
        $table = new Contract($this->container->mysql,$this->container,$table_name);

        $table->setup(['type'=>'REPEAT']);
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All repeat contracts';
        $template['javascript'] = $table->getJavascript();
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}
