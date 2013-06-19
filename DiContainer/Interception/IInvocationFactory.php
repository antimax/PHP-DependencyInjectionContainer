<?php namespace DiContainer\Interception;

interface IInvocationFactory
{
    /**
     * @param $instance
     * @param $methodName
     * @param array $params
     * @return IInvocation
     */
    function Create($instance, $methodName, array $params);
}
