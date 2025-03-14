<?php

namespace Rfuehricht\Sanitizegp\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rfuehricht\Sanitizegp\Actions\AbstractAction;
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
                $getParameters = $request->getQueryParams();
                $postParameters = $request->getParsedBody();

                foreach ($configuration['sanitizegp'] as $parameter => $actions) {
                    if ((isset($getParameters[$parameter]) || isset($postParameters[$parameter]))
                        && is_array($actions)) {
                        foreach ($actions as $options) {
                            $action = $options['action'] ?? '';
                            if (strlen(trim($action)) > 0) {
                                $className = ucfirst($action);
                                if (array_key_exists($action, $this->classAliasMap)) {
                                    $className = $this->classAliasMap[$action];
                                }
                                if (!str_contains($className, '\\')) {
                                    $className = 'Rfuehricht\\Sanitizegp\\Actions\\' . $className . 'Action';
                                }
                                $className = ltrim($className, '\\');
                                /** @var AbstractAction $actionObject */
                                $actionObject = GeneralUtility::makeInstance($className);


                                if (!isset($options['scope']) || !is_array($options['scope'])) {
                                    $options['scope'] = ['get', 'post'];
                                }

                                foreach ($options['scope'] as &$scope) {
                                    $scope = strtolower($scope);
                                }

                                if (in_array('get', $options['scope']) && isset($getParameters[$parameter])) {
                                    $getParameters[$parameter] = $actionObject->execute($getParameters[$parameter], $options);
                                }
                                if (in_array('post', $options['scope']) && isset($postParameters[$parameter])) {
                                    $postParameters[$parameter] = $actionObject->execute($postParameters[$parameter], $options);
                                }
                            }
                        }
                    }
                }

                $request = $request->withQueryParams($getParameters)->withParsedBody($postParameters);
            }
        }

        return $handler->handle($request);
    }
}
