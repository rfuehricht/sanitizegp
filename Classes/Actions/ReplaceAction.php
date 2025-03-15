<?php

namespace Rfuehricht\Sanitizegp\Actions;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ReplaceAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute(mixed $value, array $options): mixed
    {

        $search = $options['search'] ?? [];
        $replace = $options['replace'] ?? [];

        $separator = $options['separator'] ?? ',';

        $replaceFunction = $options['replaceFunction'] ?? 'str_ireplace';

        if (!is_array($search)) {
            $search = GeneralUtility::trimExplode($separator, $search);
        }
        if (!is_array($replace)) {
            $replace = GeneralUtility::trimExplode($separator, $replace);
        }

        if ($replaceFunction !== 'preg_replace') {
            foreach ($search as &$searchValue) {
                $searchValue = GeneralUtility::trimExplode($separator, $searchValue);
            }
            unset($searchValue);
            foreach ($replace as &$replaceValue) {
                $replaceValue = GeneralUtility::trimExplode($separator, $replaceValue);
            }
            unset($replaceValue);
        }

        if (isset($options['fileSource'])) {
            $fileSource = $options['fileSource'];
            if (!str_starts_with($fileSource, '/')) {
                $fileSource = rtrim(Environment::getProjectPath(), '/') . '/' . $fileSource;
            }
            if (file_exists($fileSource)) {
                $fileData = file_get_contents($fileSource);
                $lines = GeneralUtility::trimExplode(PHP_EOL, $fileData);
                foreach ($lines as $line) {
                    $lineParts = GeneralUtility::trimExplode(' => ', $line);
                    if ($replaceFunction === 'preg_replace') {
                        $search[] = $lineParts[0];
                        $replace[] = $lineParts[1];
                    } else {
                        $search[] = GeneralUtility::trimExplode($separator, $lineParts[0]);
                        $replace[] = GeneralUtility::trimExplode($separator, $lineParts[1]);
                    }
                }
            }
        }


        foreach ($search as $idx => $searchItem) {
            if (is_array($searchItem) && count($searchItem) === 1) {
                $searchItem = reset($searchItem);
            }
            $replacement = $replace[$idx];
            if (is_array($replacement) && count($replacement) === 1) {
                $replacement = reset($replacement);
            }
            $value = match ($replaceFunction) {
                'preg_replace' => preg_replace($searchItem, $replacement, $value),
                'str_replace' => str_replace($searchItem, $replacement, $value),
                default => str_ireplace($searchItem, $replacement, $value),
            };
        }
        return $value;
    }
}
