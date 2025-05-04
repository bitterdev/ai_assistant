<?php

defined('C5_EXECUTE') or die('Access denied');

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;

/** @var string|null $apiKey */
/** @var string|null $model */
/** @var array $models */

$app = Application::getFacadeApplication();
/** @var Form $form */
/** @noinspection PhpUnhandledExceptionInspection */
$form = $app->make(Form::class);
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
    <?php echo $token->output("update_settings"); ?>

    <div class="form-group">
        <?php echo $form->label("apiKey", t('API Key')); ?>
        <?php echo $form->password("apiKey", $apiKey); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label("model", t('Model')); ?>
        <?php echo $form->select("model", $models, $model); ?>
    </div>

    <p class="text-muted">
        <?php echo t("To use the AI Assistant, you need an API key from OpenAI. %s to to generate your API key.",
            sprintf(
                "<a href=\"https://platform.openai.com/account/api-keys\" target=\"_blank\">%s</a>",
                t("Click here")
            )
        ); ?>
    </p>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions ">
            <button type="submit" class="btn btn-primary float-end">
                <i class="fas fa-save"></i> <?php echo t("Save"); ?>
            </button>
        </div>
    </div>
</form>
