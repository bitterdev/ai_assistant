<?php /** @noinspection PhpUnused */

namespace Concrete\Package\AiAssistant\Controller\SinglePage\Dashboard\AiAssistant;

use Bitter\AiAssistant\ContentGenerator\Service;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Form\Service\Validation;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Entity\Page\Template as TemplateEntity;
use Exception;

class ContentGenerator extends DashboardPageController
{
    /** @var Request */
    protected $request;
    /** @var Validation */
    protected Validation $formValidator;
    protected Service $contentGeneratorService;

    public function on_start()
    {
        parent::on_start();

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->request = $this->app->make(Request::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->formValidator = $this->app->make(Validation::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->contentGeneratorService = $this->app->make(Service::class);
    }

    public function view()
    {
        if ($this->request->getMethod() === 'POST') {
            $this->formValidator->setData($this->request->request->all());

            $this->formValidator->addRequiredToken("generate_content");
            $this->formValidator->addRequired("parentPage", t("You need to enter a valid parent page."));
            $this->formValidator->addRequired("pageName", t("You need to enter a valid page name."));
            $this->formValidator->addRequired("pageType", t("You need to enter a valid page type."));
            $this->formValidator->addRequired("pageTemplate", t("You need to enter a valid page template."));
            $this->formValidator->addRequired("prompt", t("You need to enter a valid prompt."));

            if ($this->formValidator->test()) {
                $parentPage = Page::getByID($this->request->request->get("parentPage"));
                $pageType = Type::getByHandle($this->request->request->get("pageType"));
                $pageTemplate = Template::getByHandle($this->request->request->get("pageTemplate"));
                $pageName = $this->request->request->get("pageName");
                $prompt = $this->request->request->get("prompt");

                if (!$parentPage instanceof Page || $parentPage->isError()) {
                    $this->error->add(t("You need to enter a valid parent page."));
                }

                if (!$pageType instanceof Type) {
                    $this->error->add(t("You need to enter a valid page type."));
                }

                if (!$pageTemplate instanceof TemplateEntity) {
                    $this->error->add(t("You need to enter a valid page template."));
                }

                if (!$this->error->has()) {
                    try {
                        $this->contentGeneratorService->generatePage($parentPage, $pageTemplate, $pageType, $pageName, $prompt);
                        $this->set('success', t("The page has been successfully created."));
                    } catch (Exception $e) {
                        $this->error->add($e->getMessage());
                    }
                }
            } else {
                $errorList = $this->formValidator->getError();

                if ($errorList instanceof ErrorList) {
                    foreach ($errorList->getList() as $error) {
                        $this->error->add($error);
                    }
                }
            }

        }

        $pageTypes = [];
        $pageTemplates = [];

        foreach (Type::getList() as $pageType) {
            if ($pageType instanceof Type) {
                $pageTypes[$pageType->getPageTypeHandle()] = $pageType->getPageTypeName();
            }
        }

        foreach (Template::getList() as $pageTemplate) {
            if ($pageTemplate instanceof TemplateEntity) {
                $pageTemplates[$pageTemplate->getPageTemplateHandle()] = $pageTemplate->getPageTemplateName();
            }
        }

        $this->set('pageTypes', $pageTypes);
        $this->set('pageTemplates', $pageTemplates);
    }

}
