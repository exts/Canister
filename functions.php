<?php
namespace Canister;

/**
 * @param $mixed
 *
 * @return Definition
 */
function bag($mixed)
{
    return new Definition(Definition::CONTAINER, $mixed);
}

/**
 * @param $mixed
 *
 * @return Definition
 */
function val($mixed)
{
    return new Definition(Definition::VALUE, $mixed);
}