<?php

defined('C5_EXECUTE') or die('Access denied');

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\PageSelector;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;

/** @var array $locales */

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
    <?php echo $token->output("bulk_translate"); ?>

    <div class="form-group">
        <?php echo $form->label("pages", t('Pages')); ?>
        <?php echo $pageSelector->selectMultipleFromSitemap("pages"); ?>
    </div>

    <p class="text-muted">
        <?php echo t("Do not translate all pages at once. There is a token limit (combined input and output length) that depends on the GPT model used. It is recommended to translate only one or two pages at a time, depending on the content size. Exceeding the limit may cause the request to fail or be cut off. For more detailed limits and model settings, please refer to your OpenAI configuration."); ?>
    </p>

    <div class="form-group">
        <?php echo $form->label("locale", t('Target Locale')); ?>
        <?php echo $form->select("locale", $locales); ?>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions ">
            <button type="submit" class="btn btn-primary float-end">
                <i class="fas fa-save"></i> <?php echo t("Translate Pages"); ?>
            </button>
        </div>
    </div>
</form>
