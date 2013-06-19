<?php namespace DiContainer\Interception;

interface IInterceptor
{
    function Intercept(IInvocation $invocation);
}