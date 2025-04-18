// noinspection JSUnresolvedVariable

(function () {
    CKEDITOR.plugins.add(
        'ai-assistant',
        {
            init: function (editor) {
                editor.addCommand("addGenerateText", {
                    exec: function () {
                        let inputText = prompt(aiAssistant_i18n.generateText.prompt);

                        $.concreteAjax({
                            url: CCM_DISPATCHER_FILENAME + "/ai_assistant/api/v1/content_generator/generate_text",
                            data: {
                                input: inputText
                            },
                            success: (r) => {
                                editor.fire('saveSnapshot');
                                editor.setData(r.output);
                                editor.fire('saveSnapshot');
                                editor.focus();
                            },
                            error: (xhr) => {
                                ConcreteAlert.error({
                                    message: ConcreteAjaxRequest.renderErrorResponse(xhr, true),
                                });
                            },
                        });

                    }
                });

                editor.ui.addButton("addGenerateText", {
                    label: aiAssistant_i18n.generateText.label,
                    icon: this.path + "addGenerateText.gif",
                    command: "addGenerateText"
                });
            }
        }
    );
})();