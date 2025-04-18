<?php

namespace Concrete\Package\AiAssistant\Controller\SinglePage\Dashboard\AiAssistant;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Form\Service\Validation;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Controller\DashboardPageController;

class Settings extends DashboardPageController
{
    /** @var Request */
    protected $request;
    /** @var Repository */
    protected Repository $config;
    /** @var Validation */
    protected Validation $formValidator;

    public function on_start()
    {
        parent::on_start();

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->request = $this->app->make(Request::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->formValidator = $this->app->make(Validation::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->config = $this->app->make(Repository::class);
    }

    public function view()
    {
        if ($this->request->getMethod() === 'POST') {
            $this->formValidator->setData($this->request->request->all());

            $this->formValidator->addRequiredToken("update_settings");

            $this->formValidator->addRequired("apiKey", t("You need to enter a valid API key."));
            $this->formValidator->addRequired("model", t("You need to enter a valid model."));

            if (!$this->formValidator->test()) {
                $this->error = $this->formValidator->getError();
            }

            if (!$this->error->has()) {
                $this->config->save("ai_assistant.api_key", $this->request->request->get("apiKey"));
                $this->config->save("ai_assistant.model", $this->request->request->get("model"));

                $this->set('success', t("The settings has been updated successfully."));
            }
        }

        $models = [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-3.5-turbo-0125' => 'GPT-3.5 Turbo (Jan 2025)',
            'gpt-4' => 'GPT-4',
            'gpt-4-0613' => 'GPT-4 (June 2023)',
            'gpt-4-1106-preview' => 'GPT-4 Turbo (Preview)',
            'text-davinci-003' => 'Text-DaVinci 003',
            'text-embedding-ada-002' => 'Embedding Ada 002',
            'whisper-1' => 'Whisper Speech-to-Text',
        ];

        $this->set('models', $models);
        $this->set('model', $this->config->get("ai_assistant.model", "gpt-3.5-turbo"));
        $this->set('apiKey', $this->config->get("ai_assistant.api_key"));
    }

}
