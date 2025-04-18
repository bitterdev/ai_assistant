<?php

namespace Bitter\AiAssistant\Provider;

use Concrete\Core\Application\Application;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Editor\EditorInterface;
use Concrete\Core\Editor\Plugin;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Html\Service\Html;
use Concrete\Core\Page\Page;
use Concrete\Core\Routing\RouterInterface;
use Bitter\AiAssistant\Routing\RouteList;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServiceProvider extends Provider
{
    protected RouterInterface $router;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Application              $app,
        RouterInterface          $router,
        EventDispatcherInterface $eventDispatcher
    )
    {
        parent::__construct($app);

        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function register()
    {
        $this->registerRoutes();
        $this->registerAssets();
        $this->registerEditorPlugins();
        $this->registerEventHandlers();
    }

    private function registerEventHandlers()
    {
        $this->eventDispatcher->addListener("on_before_render", function () {
            $c = Page::getCurrentPage();
            if ($c instanceof Page && !$c->isError()) {
                /** @var Html $htmlService */
                $htmlService = $this->app->make(Html::class);
                $v = View::getInstance();
                $v->addHeaderItem($htmlService->javascript(Url::to("/ai_assistant/assets/localization/ai_assistant/js")));
            }
        });
    }

    private function registerEditorPlugins()
    {
        /** @var EditorInterface $editor */
        /** @noinspection PhpUnhandledExceptionInspection */
        $editor = $this->app->make(EditorInterface::class);
        $pluginManager = $editor->getPluginManager();

        $plugin = new Plugin();
        $plugin->setKey('ai-assistant');
        $plugin->setName(t('AI Assistant'));
        /** @noinspection PhpUnhandledExceptionInspection */
        $plugin->requireAsset('javascript', 'editor/ckeditor4/ai-assistant');

        if (!$pluginManager->isAvailable($plugin)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $pluginManager->register($plugin);
        }

        if (!$pluginManager->isSelected($plugin)) {
            $key = $plugin->getKey();
            $pluginManager->select($key);
        }
    }

    private function registerAssets()
    {
        $al = AssetList::getInstance();
        $al->register('javascript', 'editor/ckeditor4/ai-assistant', 'js/ckeditor4/plugins/ai-assistant/register.js', [], 'ai_assistant');
    }

    private function registerRoutes()
    {
        $this->router->loadRouteList(new RouteList());
    }
}