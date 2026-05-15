<?php

namespace App\Infrastructure;

use ReflectionClass;

/**
 * Lightweight Dependency Injection Container.
 */
class ServiceContainer
{
    /**
     * @var array<string, string|callable>
     */
    private array $bindings = [];

    public function bind(string $interface, string|callable $class): void
    {
        $this->bindings[$interface] = $class;
    }

    /**
     * Resolves a class and injects its dependencies using Reflection.
     * 
     * @template T
     * @param class-string<T> $class
     * @return T|object|null
     */
    public function get(string $class): ?object
    {
        if (isset($this->bindings[$class])) {
            $bound = $this->bindings[$class];

            if (is_callable($bound)) {
                return $bound($this);
            }

            $class = $bound;
        }

        $reflector = new ReflectionClass($class);

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
