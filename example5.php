<?php

interface IA
{
}

class A implements IA
{
    private $scalar = null;

    public function __construct($scalar)
    {
        $this->scalar = $scalar;
    }
}

class  TopDependency
{
}

require_once 'DiContainer/Container.php';

$container = new DiContainer\Container();
$container->RegisterType('IA', 'A')->RegisterParameterValue('scalar', 'this is scalar value');
$instance = $container->Resolve('IA');

print_r($instance);
