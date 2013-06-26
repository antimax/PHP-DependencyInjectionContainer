PHP-DependencyInjectionContainer
================================

PHP reflection-based dependency injection container with auto-wiring, auto-registration and interception support

What is it?
-----------

This thing here is a result of an effort to implement a “mature” dependency injection container for PHP. Out of three must-have features which the “mature” DI container should address, this implementation supports object composition and object lifetime. Interception is not supported, but it can be added quite simply though.

[This excellent book](http://www.amazon.com/Dependency-Injection-NET-Mark-Seemann/dp/1935182501/) will give you comprehensive understanding of dependency injection ideology and dependency injection containers. Glossary of the book contains references to some other extremely valuable sources of information about SOLID object-oriented programming.

Requirements
------------

PHP 5.3.9 and above.

Disadvantages
-------------

Since the container is reflection-based, it entirely relies on the type hinting in constructor declaration. That could be a problem for already existing projects being refactored, but I have strong opinion that for newly created PHP code the type hinting should be a “must have” feature.

Since the container does all sanity checks for registrations, it requires all involved classes and interfaces to be declared prior the registration. This means that you should have “required_once” PHP files declaring classes and interfaces before registration phase. DI container package provides simple source code loader that addresses this issue.

Advantages
----------

Advantages of this implementation of DI container have the same roots as the disadvantages – it is reflection-based. As a result, we have:

* Instance registration
* Full auto-wiring support
* Automatic injection of a declared class instance
* Injection of a constructor scalar parameter
* Advanced injection of a constructor scalar parameter
* Auto-registration
* Singleton and transient lifestyle support
* Cyclic dependencies detection
* Full and out-of-the-box namespaces support
* Simple configuration
* Simple and laconic source code

Interception
------------

Take a look at Stream.php. More details are coming later.

Configurators and the .ini-based container configuration
--------------------------------------------------------

More details are coming later.

Source code loader
------------------

More details are coming later.

Examples
--------

**1. Instance registration and injection**

    <?php
    
    interface IA
    {
    }
    
    interface IB
    {
    }
    
    class A implements IA
    {
        private $dependency = null;
    
        public function __construct(IB $dep)
        {
            $this->dependency = $dep;
        }
    }
    
    class  B implements IB
    {
    }
    
    require_once 'DiContainer/Container.php';
    
    $container = new DiContainer\Container();
    $container->RegisterInstance('IB', new B());
    $container->RegisterType('IA', 'A');
    
    $instance = $container->Resolve('IA');
    print_r($instance);

Here we have the interface `IA` and its implementation – the `A` class. The constructor of the class `A` has dependency on `IB`. The `B` class implements the interface `IB`.

`$container->RegisterInstance('IB', new B())` instructs the container to return a here instantiated instance of the class `B` every time the container is asked to resolve request for an instance of implementation of interface `IB`.

`$container->RegisterType('IA', 'A')` instructs the container to create an instance of class `A` every time the container is asked to resolve request for an instance of implementation of interface `IA`.

    A Object
    (
        [dependency:A:private] => B Object
            (
            )
    )

__*Note 1*__

You can do registrations in arbitrary mode. Therefore, this code works:

    $container->RegisterType('IA', 'A');
    $container->RegisterInstance('IB', new B());
    
__*Note 2*__

All registration calls (`registerInstance`, `RegisterType`, `RegisterTypeMappingRule`, `RegisterParameterValue`, `RegisterParameterCallback`) returns instance of the container. Therefore, registrations chaining works as well:

    $container->RegisterType('IA', 'A')->RegisterInstance('IB', new B());
    
__*Note 3*__

Container always returns the same instance which is originally registered by `$container->RegisterInstance(…)` call.

**2. Full auto-wiring support**

    <?php
    
    require_once 'DiContainer/Container.php';
    
    interface IA
    {
    }
    
    interface IB
    {
    }
    
    interface IC
    {
    }
    
    interface ID
    {
    }
    
    class A implements IA
    {
        private $b = null;
        private $c = null;
    
        public function __construct(IB $b, IC $c)
        {
            $this->b = $b;
            $this->c = $c;
        }
    }
    
    class B implements IB
    {
        private $d = null;
    
        function __construct(ID $d)
        {
            $this->d = $d;
        }
    }
    
    class C implements IC
    {
    }
    
    
    class D implements ID
    {
    }
    
    $container = new DiContainer\Container();
    $container->RegisterType('IA', 'A')
        ->RegisterType('IB', 'B')
        ->RegisterType('IC', 'C')
        ->RegisterType('ID', 'D');
    
    $instance = $container->Resolve('IA');
    
    print_r($instance);


Here we have the class `A` that implements the interface `IA`. The constructor of the class `A` depends on the interfaces `IB` and `IC`. The classes `B` and `C` implement the interfaces `IB` and `IC` respectively. The constructor of the class `B` depends on the interface `ID`.  We merely instruct the container which types implement the interfaces. The container does all the wiring automatically.

    A Object
    (
        [b:A:private] => B Object
            (
                [d:B:private] => D Object
                    (
                    )
            )
    
        [c:A:private] => C Object
            (
            )
    )

__*Note 1*__

You can use `$container-> RegisterTypeMappingRule(…)` to make configuration even simpler.

**3. Namespaces and auto-registration**

    <?php
    
    namespace Interfaces
    {
        interface IA
        {
        }
    
        interface IB
        {
        }
    
        interface IC
        {
        }
    
        interface ID
        {
        }
    }
    
    namespace Implementations
    {
        class A implements \Interfaces\IA
        {
            private $b = null;
            private $c = null;
    
            public function __construct(\Interfaces\IB $b, \Interfaces\IC $c)
            {
                $this->b = $b;
                $this->c = $c;
            }
        }
    
        class B implements \Interfaces\IB
        {
            private $d = null;
    
            function __construct(\Interfaces\ID $d)
            {
                $this->d = $d;
            }
        }
    
        class C implements \Interfaces\IC
        {
        }
    
    
        class D implements \Interfaces\ID
        {
        }
    }
    
    namespace
    {
        require_once 'DiContainer/Container.php';
    
        $container = new DiContainer\Container();
        $container->RegisterTypeMappingRule('~^Interfaces\\\I(.+)~', 'Implementations\\\${1}');
        $instance = $container->Resolve('Interfaces\IA');
    
        print_r($instance);
    }

Here we have a variant of the previous example that demonstrates the out-of-the-box namespaces support and the auto-registration feature. As you can see, every interface in the namespace `Interfaces` is implemented by a class in the namespace `Implementations`. You can also see that there is a direct dependency of implementation name on an interface name: Interfaces\I**Name** is implemented by Implementations\**Name**.

PHP provides us with the great function `preg_replace` that allows us to create a rule that generalizes this dependency. The only thing that `$container->RegisterTypeMappingRule(…)` actually does is that it iterates through all abstract types (interfaces and abstract classes) declared by the moment of the method call and uses the `preg_replace` function to try to construct a name of implementation class. If it has succeeded, `$container->RegisterType(…)` is called.

    Implementations\A Object
    (
        [b:Implementations\A:private] => Implementations\B Object
            (
                [d:Implementations\B:private] => Implementations\D Object
                    (
                    )
    
            )
        [c:Implementations\A:private] => Implementations\C Object
            (
            )
    )
    
__*Note 1*__    

The first and the second parameters of the `RegisterTypeMappingRule` method are actually first and second parameters of the `preg_replace` function. The third parameter of the `preg_replace` function is an abstract type name.

__*Note 2*__

Namespaces in this example limit the scope of the rule to prevent it from being too generic.

**4. Automatic injection of a declared class instance**

    <?php
    
    interface IA
    {
    }
    
    class A implements IA
    {
        private $b = null;
    
        public function __construct(B $b)
        {
            $this->b = $b;
        }
    }
    
    class B
    {
        private $c = null;
    
        public function __construct(C $c)
        {
            $this->c = $c;
        }
    }
    
    class  C
    {
    }
    
    require_once 'DiContainer/Container.php';
    
    $container = new DiContainer\Container();
    $container->RegisterType('IA', 'A');
    $instance = $container->Resolve('IA');
    
    print_r($instance);

Here we have the class `A` that implements the interface `IA`. The constructor of the class `A` depends on an instance of the non-abstract class `B`. The constructor of the class `B`, in turn, depends on the instance of some other non-abstract class `C`. In this case, the container creates instances of class `B` and class `C` without any additional instructions. Auto-wiring works as well.

    A Object
    (
        [b:A:private] => B Object
            (
                [c:B:private] => C Object
                    (
                    )
            )            
    )
    
__*Note 1*__

Unfortunately, by this moment the latest stable version of PHP (5.4.12) does not allow Reflection API to work with built-in classes. This means you cannot use, for instance, `Exception` in place of, say, `B` in this example.

__*Note 2*__

If a constructor has dependency on an interface or a class (not on a scalar), auto-wiring will try to create instance even if this parameter has default value `null` specified.

**5. Injection of a constructor scalar parameter**

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

Here we have the class `A` that implements the interface `IA`. The class `A` constructor depends on the scalar `$scalar`. We can easily instruct the container how to handle that.

    A Object
    (
        [scalar:A:private] => this is scalar value
    )

__*Note 1*__

If a constructor scalar parameter has a default value specified, then it is used in the case when this scalar parameter is not registered with the container.

**6. Advanced injection of a constructor scalar parameter**

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
    
Here we have the interfaces `IA` and `IB` and their implementations. Both implementations depend on the scalar parameter `$scalar`. We want this scalar parameter to have a different value for different implementations. `$container->RegisterParameterCallback(…)` allows us to defined a call-back function that gets information about the class being instantiated as a parameter of the `ReflectionClass` type.    

    A Object
    (
        [b:A:private] => B Object
            (
                [scalar:B:private] => The scalar value for an instance of the class B
            )
        [scalar:A:private] => The scalar value for an instance of the class A
    )

__*Note 1*__

`$container->RegisterParameterValue(…)` actually creates a closure and then calls `$container->RegisterParameterCallback(…)`.

**7. Singleton lifestyle support**

    <?php
    
    require_once 'DiContainer/Container.php';
    
    interface IA
    {
    }
    
    interface IB
    {
    }
    
    interface IC
    {
    }
    
    interface ID
    {
    }
    
    class A implements IA
    {
        public $b = null;
        public $c = null;
    
        public function __construct(IB $b, IC $c)
        {
            $this->b = $b;
            $this->c = $c;
        }
    }
    
    class B implements IB
    {
        public $d = null;
    
        public function __construct(ID $d)
        {
            $this->d = $d;
        }
    }
    
    class C implements IC
    {
        public $d = null;
    
        public function __construct(ID $d)
        {
            $this->d = $d;
        }
    }
    
    class D implements ID
    {
    }
    
    
    $container = new DiContainer\Container();
    $container->RegisterType('IA', 'A')
        ->RegisterType('IB', 'B')
        ->RegisterType('IC', 'C')
        ->RegisterType('ID', 'D', true);
    
    $instance = $container->Resolve('IA');
    print_r($instance);
    print $instance->b->d === $instance->c->d ? 'TRUE' : 'FALSE';
    
    
Here we have four interfaces: `IA`, `IB`, `IC` and `ID`. The Class `A` that implements the interface `IA` has dependency on `IB` and `IC`. The classes `B` and `C` that implement the interfaces `IB` and `IC` respectively have dependency on the interface `ID`. The class `D` that implements the interface `ID` does not have dependencies. The class `D` is registered as implementation of the `ID` interface with singleton lifestyle flag set therefore just one instance of the class `D` is created and the result of `$instance->b->d === $instance->c->d ? 'TRUE' : 'FALSE';` is `‘TRUE’`.

    A Object
    (
        [b] => B Object
            (
                [d] => D Object
                    (
                    )
    
            )
    
        [c] => C Object
            (
                [d] => D Object
                    (
                    )
    
            )
    
    )
    TRUE

__*Note 1*__

Transient is the default lifestyle.

**8. Cyclic dependencies detection**

    <?php
    
    require_once 'DiContainer/Container.php';
    
    interface IA
    {
    }
    
    interface IB
    {
    }
    
    interface IC
    {
    }
    
    interface ID
    {
    }
    
    class A implements IA
    {
        public $b = null;
    
        public function __construct(IB $b)
        {
            $this->b = $b;
        }
    }
    
    class B implements IB
    {
        public $c = null;
    
        public function __construct(IC $c)
        {
            $this->c = $c;
        }
    }
    
    class C implements IC
    {
        public $d = null;
    
        public function __construct(ID $d)
        {
            $this->d = $d;
        }
    }
    
    class D implements ID
    {
        public $a = null;
    
        public function __construct(IA $a)
        {
            $this->a = $a;
        }
    }
    
    
    $container = new DiContainer\Container();
    $container->RegisterType('IA', 'A')
        ->RegisterType('IB', 'B')
        ->RegisterType('IC', 'C')
        ->RegisterType('ID', 'D');
    
    $instance = $container->Resolve('IA');

Here we have four interfaces `IA`, `IB`, `IC` and `ID` and their implementations – classes `A`, `B`, `C` and `D`. `A` depends on `IB`, `B` depends on `IC`, `C` depends on `ID` and `D` depends on `IA`, thus we have the dependencies cycle. The container detects this situation and throws exception rather than to stuck in an infinite loop.

    PHP Fatal error:  Uncaught exception 'InvalidArgumentException' with message 'Cyclic dependency on the type 'IA' detected' in /home/max/projects/container/DiContainer/Container.php:207
    Stack trace:
    #0 /container/DiContainer/Container.php(235): DiContainer\Container->RecursiveResolve('IA', Array)
    #1 /container/DiContainer/Container.php(235): DiContainer\Container->RecursiveResolve('ID', Array)
    #2 /container/DiContainer/Container.php(235): DiContainer\Container->RecursiveResolve('IC', Array)
    #3 /container/DiContainer/Container.php(235): DiContainer\Container->RecursiveResolve('IB', Array)
    #4 /container/DiContainer/Container.php(169): DiContainer\Container->RecursiveResolve('IA', Array)
    #5 /container/example8.php(68): DiContainer\Container->Resolve('IA')
    #6 {main}
      thrown in /container/DiContainer/Container.php on line 207



