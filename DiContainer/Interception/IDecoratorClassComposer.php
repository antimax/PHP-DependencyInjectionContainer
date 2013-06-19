<?php namespace DiContainer\Interception;


interface IDecoratorClassComposer
{
    function Compose(\ReflectionClass $decoratedType, $decoratorClassName);
}