<?php

namespace Rfuehricht\Sanitizegp\Actions;

class HtmlSpecialCharsAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {
        return htmlspecialchars($value);
    }
}
