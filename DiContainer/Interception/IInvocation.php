<?php namespace DiContainer\Interception;

interface IInvocation
{
    /**
     * @return mixed
     */
    function Proceed();

    /**
     * @return \ReflectionClass
     */
    function GetDecoratedType();

    /**
     * @return object
     */
    function GetDecoratedInstance();

    /**
     * @return \ReflectionMethod
     */
    function GetMethod();

    /**
     * @return &mixed[]
     */
    function &GetMethodParameters();

    /**
     * @return &mixed
     */
    function &GetResult();
}