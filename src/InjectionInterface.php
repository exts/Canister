<?php

namespace Canister;

interface InjectionInterface
{
    public function alias($class, $alias);
    public function define($class, array $parameters);
    public function factory($class, $callable);
}