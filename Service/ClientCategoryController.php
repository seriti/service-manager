<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\ClientCategory;

class ClientCategoryController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'client_category';
        $table = new ClientCategory($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Client categorys';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
