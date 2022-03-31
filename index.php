<?php

require_once 'vendor/autoload.php';

class Example {
    private Dependency $dependency;

    public function __construct(Dependency $dependency)
    {
        $this->dependency = $dependency;
    }
}

class Dependency {
    private DependencyDependency $dependency;

    public function __construct(DependencyDependency $dependency)
    {
        $this->dependency = $dependency;
    }
}

class DependencyDependency {

}

$wirer = new \PrimativeAutoWirer\AutoWire();
$class = $wirer->autoWire(Example::class);

var_dump($class);