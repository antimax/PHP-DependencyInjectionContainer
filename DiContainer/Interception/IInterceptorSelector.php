<?php namespace DiContainer\Interception;

interface IInerceptorSelector
{
    /**
     * @param \ReflectionClass $decoratedType
     * @return IInterceptor[]
     */
    function GetInterceptors(\ReflectionClass $decoratedType);
}