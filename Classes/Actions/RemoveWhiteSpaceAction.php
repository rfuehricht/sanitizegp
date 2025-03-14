<?php

namespace Rfuehricht\Sanitizegp\Actions;

class RemoveWhiteSpaceAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {
        $search = [' ', "\n", "\t", "\r"];
        return str_replace($search, '', $value);
    }
}
