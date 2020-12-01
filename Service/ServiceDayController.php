<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\ServiceDay;

class ServiceDayController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'service_day';
        $table = new ServiceDay($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Service days';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
