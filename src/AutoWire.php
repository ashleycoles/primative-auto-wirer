<?php

declare(strict_types=1);

namespace PrimativeAutoWirer;


use PrimativeAutoWirer\Exceptions\AutowireException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class AutoWire
{
    /**
     * @throws AutowireException
     * @throws ReflectionException
     */
    public function autoWire(string $class): ?object
    {
        if (!class_exists($class)) {
            throw new AutowireException("Class $class does not exist");
        }

        $reflection = $this->getReflection($class);
        $reflectionConstructor = $reflection->getConstructor();

        // If the class doesn't have a constructor we can just instantiate it right away
        if (!$reflectionConstructor) {
            return $reflection->newInstance();
        }

        /**
         * @var ReflectionParameter[] $constructorParams
         */
        $constructorParams = $this->loadConstructorParams($reflectionConstructor);

        return $reflection->newInstanceArgs($constructorParams);
    }

    /**
     * Helper to create a ReflectionClass
     *
     * Makes sure the target class is able to be instantiated otherwise throws an AutowireException
     *
     * @param string $class
     *
     * @return ReflectionClass
     *
     * @throws AutowireException
     * @throws ReflectionException
     */
    private function getReflection(string $class): ReflectionClass
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new AutowireException("Class $class cannot be instantiated");
        }

        return $reflection;
    }

    /**
     * @param ReflectionMethod $reflectionConstructor
     * @param string $class
     * @param array $constructorParams
     * @return array
     * @throws AutowireException
     * @throws ReflectionException
     */
    private function loadConstructorParams(ReflectionMethod $reflectionConstructor): array
    {
        $constructorParams = [];
        $reflectionConstructorParams = $reflectionConstructor->getParameters();
        // If there are no params then just return an empty array.
        if (empty($reflectionConstructorParams)) return $constructorParams;

        foreach ($reflectionConstructorParams as $reflectionConstructorParam) {
            // We can autowire class dependencies by looking at their types
            $paramTypeReflection = $reflectionConstructorParam->getType();

            // If the constructor param has no type, we can't autowire it
            if (!$paramTypeReflection) {
                throw new AutowireException("Class '" .
                    $reflectionConstructorParam->getDeclaringClass()->getName() .
                    "' constructor param '" .
                    $reflectionConstructorParam->getName() .
                    "' missing type - unable to autowire");
            }
            // Create a reflection of the dependency
            $paramReflection = $this->getReflection($paramTypeReflection->getName());
            // Grab the dependencies constructor
            $paramReflectionConstructor = $paramReflection->getConstructor();

            // If the dependency has a constructor run it through autoWire()
            // Otherwise create an instance directly
            $constructorParams[] = $paramReflectionConstructor ? $this->autoWire($paramTypeReflection->getName()) : $paramReflection->newInstance();
        }
        return $constructorParams;
    }
}