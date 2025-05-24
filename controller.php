<?php

namespace Concrete\Package\AiAssistant;

use Bitter\AiAssistant\Provider\ServiceProvider;
use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Package\Package;

class Controller extends Package
{
    protected string $pkgHandle = 'ai_assistant';
    protected string $pkgVersion = '0.0.3';
    protected $appVersionRequired = '9.0.0';
    protected $pkgAutoloaderRegistries = [
        'src/Bitter/AiAssistant' => 'Bitter\AiAssistant',
    ];

    public function getPackageDescription(): string
    {
        return t('AI Assistant integrates powerful AI features like content generation, translation, and SEO automation directly into Concrete CMS.');
    }

    public function getPackageName(): string
    {
        return t('AI Assistant');
    }

    public function on_start()
    {
        require_once ("vendor/autoload.php");
        
        /** @var ServiceProvider $serviceProvider */
        /** @noinspection PhpUnhandledExceptionInspection */
        $serviceProvider = $this->app->make(ServiceProvider::class);
        $serviceProvider->register();
    }

    public function install(): PackageEntity
    {
        $pkg = parent::install();
        $this->installContentFile("data.xml");
        return $pkg;
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->installContentFile("data.xml");
    }
}