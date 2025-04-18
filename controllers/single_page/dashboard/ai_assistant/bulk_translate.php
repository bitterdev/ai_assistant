<?php /** @noinspection DuplicatedCode */

/** @noinspection PhpUnused */

namespace Concrete\Package\AiAssistant\Controller\SinglePage\Dashboard\AiAssistant;

use Bitter\AiAssistant\ContentGenerator\Service;
use Concrete\Core\Entity\Site\Locale;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Form\Service\Validation;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Page;
use Concrete\Core\Site\Service as SiteService;
use Exception;

class BulkTranslate extends DashboardPageController
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
        $locales = [];

        /** @var SiteService $siteService */
        /** @noinspection PhpUnhandledExceptionInspection */
        $siteService = $this->app->make(SiteService::class);
        $site = $siteService->getSite();
        $siteLocales = $site->getLocales();

        foreach ($siteLocales as $locale) {
            if ($locale instanceof Locale) {
                $locales[$locale->getLocale()] = $locale->getLocale();
            }
        }

        if ($this->request->getMethod() === 'POST') {
            $this->formValidator->setData($this->request->request->all());

            $this->formValidator->addRequiredToken("bulk_translate");
            $this->formValidator->addRequired("locale", t("You need to enter a valid locale."));

            if ($this->formValidator->test()) {
                $pages = [];

                if (is_array($this->request->request->get("pages", []))) {
                    foreach ($this->request->request->get("pages", []) as $cID) {
                        $page = Page::getByID($cID);

                        if ($page instanceof Page && !$page->isError()) {
                            $pages[] = $page;
                        } else {
                            $this->error->add(t("You need to enter at least one valid page."));
                            break;
                        }
                    }
                }

                if (count($pages) === 0) {
                    $this->error->add(t("You need to enter at least one valid page."));
                }

                $locale = $this->request->request->get("locale");

                if (!in_array($locale, $locales)) {
                    $this->error->add(t("You need to enter a valid locale."));
                }

                if (!$this->error->has()) {
                    try {
                        $this->contentGeneratorService->bulkTranslatePages($pages, $locale);

                        $this->set('success', t("The pages has been successfully translated."));
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

        $this->set('locales', $locales);
    }

}
