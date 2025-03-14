<?php

namespace Rfuehricht\Sanitizegp\Actions;

class ConvertAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {
        $type = $options['type'] ?? '';
        return match ($type) {
            'integer', 'int' => intval($value),
            'double', 'float' => floatval($value),
            default => $value
        };

    }
}
