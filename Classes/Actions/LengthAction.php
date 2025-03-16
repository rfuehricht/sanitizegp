<?php

namespace Rfuehricht\Sanitizegp\Actions;

class LengthAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {
        $value = (string)$value;

        if (isset($options['trim']) && boolval($options['trim']) === true) {
            $value = trim($value);
        }
        if (isset($options['min'])) {
            $minLength = intval($options['min']);
            if (strlen($value) < $minLength) {
                $padString = $options['padString'] ?? '.';
                $padType = match ($options['padType'] ?? 'right') {
                    'left' => STR_PAD_LEFT,
                    'both' => STR_PAD_BOTH,
                    default => STR_PAD_RIGHT,
                };
                $value = str_pad($value, $minLength, $padString, $padType);
            }
        }
        if (isset($options['max'])) {
            $maxLength = intval($options['max']);
            if (strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return $value;
    }
}
