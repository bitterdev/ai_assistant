<?php

namespace Concrete\Package\AiAssistant\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;

class AiAssistant extends DashboardPageController
{
    public function view()
    {
        return $this->buildRedirectToFirstAccessibleChildPage();
    }
}
