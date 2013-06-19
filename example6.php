<?php

require_once 'DiContainer/Container.php';

interface IA
{
}

interface IB
{
}

class A implements IA
{
    private $b = null;
    private $scalar = null;

    public function __construct(IB $b, $scalar)
    {
        $this->b = $b;
        $this->scalar = $scalar;
    }
}

class B implements IB
{
    private $scalar = null;

    function __construct($scalar)
    {
        $this->scalar = $scalar;
    }
}

$container = new DiContainer\Container();
$container->RegisterType('IA', 'A')
    ->RegisterType('IB', 'B')
    ->RegisterParameterCallback('scalar', function (ReflectionClass $reflectionClass) {
        return "The scalar value for an instance of the class {$reflectionClass->getName()}";

    });

$instance = $container->Resolve('IA');

print_r($instance);
