<?php

namespace Rfuehricht\Sanitizegp\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rfuehricht\Sanitizegp\Actions\AbstractAction;
use Rfuehricht\Sanitizegp\Helper\SeparatorArrayAccess;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;


final readonly class SanitizeGetPost implements MiddlewareInterface
{

    private array $classAliasMap;

    public function __construct()
    {
        $this->classAliasMap = [
            'int' => 'Integer'
        ];

    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        /** @var Site $site */
        $site = $request->getAttribute('site');


        if ($site) {
            $configuration = $site->getConfiguration();

            if (isset($configuration['sanitizegp']) && is_array($configuration['sanitizegp'])) {

                $settings = $configuration['sanitizegp']['settings'] ?? [];
                unset($configuration['sanitizegp']['settings']);

                $separator = $settings['separator'] ?? '|';

                $getParameters = new SeparatorArrayAccess($request->getQueryParams(), $separator);
                $postParameters = new SeparatorArrayAccess($request->getParsedBody(), $separator);


                foreach ($configuration['sanitizegp'] as $parameter => $actions) {


                    //Workaround as global wildcard doesn't work as key in YAML configuration
                    if ($parameter === 'all') {
                        $parameter = '*';
                    }

                    if (($getParameters->has($parameter) || $postParameters->has($parameter))
                        && is_array($actions)) {
                        foreach ($actions as $options) {
                            $action = $options['action'] ?? '';
                            if (strlen(trim($action)) > 0) {
                                $actionObject = $this->getActionObject($action);

                                if (!isset($options['scope']) || !is_array($options['scope'])) {
                                    $options['scope'] = ['get', 'post'];
                                }

                                foreach ($options['scope'] as &$scope) {
                                    $scope = strtolower($scope);
                                }

                                if (in_array('get', $options['scope']) && $getParameters->has($parameter)) {
                                    $getParameters = $this->processParameterArray(
                                        $getParameters,
                                        $parameter,
                                        $actionObject,
                                        $options
                                    );
                                }
                                if (in_array('post', $options['scope']) && $postParameters->has($parameter)) {
                                    $postParameters = $this->processParameterArray(
                                        $postParameters,
                                        $parameter,
                                        $actionObject,
                                        $options
                                    );
                                }
                            }
                        }
                    }
                }
                $request = $request->withQueryParams($getParameters->all())
                    ->withParsedBody($postParameters->all());
            }
        }
        return $handler->handle($request);
    }

    private function getActionObject(string $action): AbstractAction
    {
        $className = ucfirst($action);
        if (array_key_exists($action, $this->classAliasMap)) {
            $className = $this->classAliasMap[$action];
        }
        if (!str_contains($className, '\\')) {
            $className = 'Rfuehricht\\Sanitizegp\\Actions\\' . $className . 'Action';
        }
        $className = ltrim($className, '\\');

        /** @var AbstractAction $actionObject */
        return GeneralUtility::makeInstance($className);
    }

    private function processParameterArray(SeparatorArrayAccess $parameters, string $parameter, AbstractAction $actionObject, array $options): SeparatorArrayAccess
    {
        $valuesToParse = $parameters->get($parameter);
        if ($valuesToParse !== null) {
            if (!is_array($valuesToParse)) {
                $valuesToParse = [$valuesToParse];
            }

            $valuesToParse = $this->performAction($valuesToParse, $actionObject, $options);
            if (count($valuesToParse) === 1) {
                $valuesToParse = $valuesToParse[0];
            }

            if ($parameter === '*') {
                $parameters->setArray($valuesToParse);
            } else {
                $parameters->set($parameter, $valuesToParse);
            }
        }
        return $parameters;
    }

    private function performAction(array $valuesToParse, AbstractAction $actionObject, array $options): array
    {
        foreach ($valuesToParse as $key => &$value) {
            if (is_array($value)) {
                $value = $this->performAction($value, $actionObject, $options);
            } else {
                $valuesToParse[$key] = $actionObject->execute($value, $options);
            }
        }
        return $valuesToParse;
    }
}
