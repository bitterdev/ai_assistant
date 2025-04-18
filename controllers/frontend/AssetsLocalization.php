<?php

namespace Concrete\Package\AiAssistant\Controller\Frontend;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AssetsLocalization extends Controller
{
    private function createJavascriptResponse(string $content): Response
    {
        /** @var ResponseFactoryInterface $rf */
        /** @noinspection PhpUnhandledExceptionInspection */
        $rf = $this->app->make(ResponseFactoryInterface::class);

        return $rf->create(
            $content,
            200,
            [
                "Content-Type" => "application/javascript; charset=" . APP_CHARSET,
            ]
        );
    }

    public function getAiAssistantJavascript(): Response
    {
        $content = "var aiAssistant_i18n = " . json_encode([
                "generateText" => [
                    "label" => t("Generate Text"),
                    "prompt" => t("Please enter your prompt:")
                ]
            ]);

        return $this->createJavascriptResponse($content);
    }
}
