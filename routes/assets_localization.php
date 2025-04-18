<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Application\Application $app
 * @var Concrete\Core\Routing\Router $router
 */

/*
 * Base path: /ai_assistant/assets/localization
 * Namespace: Concrete\Package\AiAssistant\Controller\Frontend
 */

$router->get('/ai_assistant/js', 'AssetsLocalization::getAiAssistantJavascript');
