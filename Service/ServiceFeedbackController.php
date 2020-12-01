<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\ServiceFeedback;

class ServiceFeedbackController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'service_feedback';
        $table = new ServiceFeedback($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Standard Service feedback';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
