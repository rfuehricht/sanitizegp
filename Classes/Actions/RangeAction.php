<?php

namespace Rfuehricht\Sanitizegp\Actions;

class RangeAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {
        $value = intval($value);
        if (isset($options['lower'])) {
            $lower = intval($options['lower']);
            if ($value < $lower) {
                $value = $lower;
            }
        }
        if (isset($options['upper'])) {
            $upper = intval($options['upper']);
            if ($value > $upper) {
                $value = $upper;
            }
        }

        return $value;
    }
}
