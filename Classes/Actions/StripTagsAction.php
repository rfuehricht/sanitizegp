<?php

namespace Rfuehricht\Sanitizegp\Actions;

class StripTagsAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {
        return strip_tags($value, $options['allowedTags'] ?? []);
    }
}
