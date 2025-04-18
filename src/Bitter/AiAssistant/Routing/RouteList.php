<?php

namespace Bitter\AiAssistant\Routing;

use Bitter\AiAssistant\API\V1\ContentGenerator;
use Bitter\AiAssistant\API\V1\Middleware\FractalNegotiatorMiddleware;
use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;

class RouteList implements RouteListInterface
{
    public function loadRoutes(Router $router)
    {
        $router
            ->buildGroup()
            ->setPrefix('/ai_assistant/api/v1')
            ->addMiddleware(FractalNegotiatorMiddleware::class)
            ->routes(function ($groupRouter) {
                /** @var $groupRouter Router */
                /** @noinspection PhpParamsInspection */
                $groupRouter->all('/content_generator/generate_text', [ContentGenerator::class, 'generateText']);
            });

        $router
            ->buildGroup()
            ->setNamespace('Concrete\Package\AiAssistant\Controller\Dialog\Support')
            ->setPrefix('/ccm/system/dialogs/ai_assistant')
            ->routes('dialogs/support.php', 'ai_assistant');


        $router->buildGroup()->setNamespace('Concrete\Package\AiAssistant\Controller\Frontend')->setPrefix('/ai_assistant/assets/localization')
            ->routes('assets_localization.php', 'ai_assistant')
        ;
    }
}