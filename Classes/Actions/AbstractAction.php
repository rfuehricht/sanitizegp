<?php

namespace Rfuehricht\Sanitizegp\Actions;

abstract class AbstractAction
{

    /**
     * @param mixed $value
     * @param array $options
     * @return mixed
     */
    abstract public function execute(mixed $value, array $options): mixed;

}
