<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\AccountCode;

class AccountCodeController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'account_code';
        $table = new AccountCode($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'External accounting system codes';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
