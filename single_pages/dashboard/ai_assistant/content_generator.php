<?php

defined('C5_EXECUTE') or die('Access denied');

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\PageSelector;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;

/** @var array $pageTypes */
/** @var array $pageTemplates */

$app = Application::getFacadeApplication();
/** @var Form $form */
/** @noinspection PhpUnhandledExceptionInspection */
$form = $app->make(Form::class);
/** @var PageSelector $pageSelector */
/** @noinspection PhpUnhandledExceptionInspection */
$pageSelector = $app->make(PageSelector::class);
/** @var Token $token */
/** @noinspection PhpUnhandledExceptionInspection */
$token = $app->make(Token::class);
?>

<div class="ccm-dashboard-header-buttons">
    <?php /** @noinspection PhpUnhandledExceptionInspection */
    View::element("dashboard/help", [], "ai_assistant"); ?>
</div>

<?php \Concrete\Core\View\View::element("dashboard/did_you_know", [], "ai_assistant"); ?>

<form action="#" method="post">
    <?php echo $token->output("generate_content"); ?>

    <div class="form-group">
        <?php echo $form->label("parentPage", t('Parent Page')); ?>
        <?php echo $pageSelector->selectFromSitemap("parentPage"); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label("pageType", t('Page Type')); ?>
        <?php echo $form->select("pageType", $pageTypes); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label("pageTemplate", t('Page Template')); ?>
        <?php echo $form->select("pageTemplate", $pageTemplates); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label("pageName", t('Page Name')); ?>
        <?php echo $form->text("pageName"); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label("prompt", t('Prompt')); ?>
        <?php echo $form->textarea("prompt", null, ["placeholder" => t("Please enter your prompt...")]); ?>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions ">
            <button type="submit" class="btn btn-primary float-end">
                <i class="fas fa-save"></i> <?php echo t("Generate Page"); ?>
            </button>
        </div>
    </div>
</form>
