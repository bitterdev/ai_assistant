<?php /** @noinspection PhpUnused */

namespace Bitter\AiAssistant\ContentGenerator;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Block\Block;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\Page\Template;
use Concrete\Core\File\Service\File;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\User\User;
use Concrete\Core\Utility\Service\Text;
use Concrete\Core\View\View;
use Concrete\Package\AiAssistant\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

class Service implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    protected Repository $config;
    protected Client $client;
    protected Connection $db;
    protected PackageService $packageService;
    protected Controller $pkg;
    protected File $fileService;

    public function __construct(
        Repository     $config,
        Connection     $db,
        PackageService $packageService,
        File           $fileService
    )
    {
        $this->config = $config;
        $this->db = $db;

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 60.0,
        ]);

        $this->packageService = $packageService;
        $this->fileService = $fileService;
        $pkgEntity = $this->packageService->getByHandle("ai_assistant");
        $this->pkg = $pkgEntity->getController();
    }

    /**
     * @throws Exception
     */
    protected function sendPrompt(
        string  $templateFile,
        array   $params = [],
        bool    $isJsonResponse = false,
        ?string $schemaFile = null
    ): string|array|null
    {
        ob_start();
        View::element("prompts/" . $templateFile, $params, "ai_assistant");
        $prompt = ob_get_contents();
        ob_end_clean();

        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->get("ai_assistant.api_key"),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->config->get("ai_assistant.model", "gpt-3.5-turbo"),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                ],
            ]);


            $data = json_decode($response->getBody()->getContents(), true);

            $content = $data['choices'][0]['message']['content'] ?? null;

            if ($isJsonResponse) {
                // Remove invalid control characters
                $json = preg_replace('/[[:cntrl:]]/', '', $content);

                // Enforce UTF-8
                $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');

                $parsedResponse = json_decode($json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception(json_last_error_msg());
                }

                if ($schemaFile !== null) {
                    $file = $this->pkg->getPackagePath() . "/schemas/" . $schemaFile;
                    $schema = json_decode($this->fileService->getContents($file));

                    $validator = new Validator();
                    $validator->validate($parsedResponse, $schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);

                    if (!$validator->isValid()) {
                        foreach ($validator->getErrors() as $error) {
                            throw new Exception($error['message']);
                        }
                    }
                }

                return $parsedResponse;
            } else {
                return $content;
            }
        } catch (GuzzleException $e) {
            $errorMessage = $e->getMessage();

            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $body = (string)$e->getResponse()->getBody();
                $json = json_decode($body, true);
                if (isset($json['error']['message'])) {
                    $errorMessage = $json['error']['message'];
                }
            }

            throw new Exception($errorMessage);
        }
    }

    protected function collectBlockData(Page $page): array
    {
        $blockData = [];

        foreach ($page->getBlockIDs() as $arrBlock) {
            if (isset($arrBlock["bID"])) {
                $bID = $arrBlock["bID"];

                $b = null;

                try {
                    $b = Block::getByID($bID);
                } catch (\Doctrine\DBAL\Exception|BindingResolutionException) {
                    // Ignore
                }

                if ($b instanceof Block) {
                    $btTable = $b->getController()->getBlockTypeDatabaseTable();

                    if (strlen($btTable) > 0) {
                        $row = [];

                        try {
                            /** @noinspection SqlNoDataSourceInspection */
                            $row = $this->db->fetchAssociative("SELECT * FROM $btTable WHERE bID = ?", [$bID]);
                        } catch (\Doctrine\DBAL\Exception) {
                            // Ignore
                        }

                        if (is_array($row)) {
                            $blockData[$bID] = $row;
                        }
                    }
                }
            }
        }

        return $blockData;
    }

    /**
     * @param array|Page[] $pages
     * @return array
     */
    protected function collectPageData(array $pages): array
    {
        $pageData = [];

        foreach ($pages as $page) {
            $pageData[] = [
                "cID" => $page->getCollectionID(),
                "blocks" => $this->collectBlockData($page)
            ];
        }

        return $pageData;
    }

    /**
     * @throws Exception
     *
     * @var array|Page[] $pages
     */
    public function optimizeSeoForMultiplePages(
        array $pages
    ): void
    {
        $parsedResponse = $this->sendPrompt(
            "seo",
            [
                "payload" => json_encode([
                    "pageData" => $this->collectPageData($pages)
                ])
            ],
            true,
            "seo-response.json"
        );

        foreach ($parsedResponse["pageData"] as $pageData) {
            $cID = $pageData["cID"];
            $title = $pageData["seo"]["title"];
            $description = $pageData["seo"]["description"];
            $keywords = $pageData["seo"]["keywords"];

            $c = Page::getByID($cID);

            if ($c instanceof Page && !$c->isError()) {
                $c->setAttribute("meta_title", $title);
                $c->setAttribute("meta_description", $description);
                $c->setAttribute("meta_keywords", $keywords);
            }
        }
    }

    /**
     * @throws Exception
     * @var array|Page[] $pages
     * @var string $targetLocale
     *
     */
    public function bulkTranslatePages(
        array  $pages,
        string $targetLocale
    ): void
    {
        $parsedResponse = $this->sendPrompt(
            "translate",
            [
                "payload" => json_encode([
                    "pageData" => $this->collectPageData($pages)
                ]),
                "targetLocale" => $targetLocale
            ],
            true,
            "translate-response.json"
        );

        foreach ($parsedResponse["pageData"] as $pageData) {
            foreach ($pageData["blocks"] as $row) {
                $bID = $row["bID"];
                $b = Block::getByID($bID);
                if ($b instanceof Block) {
                    $tableName = $b->getController()->getBlockTypeDatabaseTable();
                    $this->db->replace($tableName, $row, ['bID']);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function generatePage(
        Page     $parentPage,
        Template $pageTemplate,
        Type     $pageType,
        string   $pageName,
        string   $contentDescription
    ): void
    {

        $u = new User();

        /** @var Text $textService */
        $textService = $this->app->make(Text::class);

        $xml = $this->sendPrompt(
            "generate_page", [
            "locale" => Localization::getInstance()->getLocale(),
            "parentPagePath" => $parentPage->getCollectionPath(),
            "userName" => $u->getUserName(),
            "pageName" => $pageName,
            "pageSlug" => $textService->urlify($pageName),
            "contentDescription" => $contentDescription,
            "pageTypeHandle" => $pageType->getPageTypeHandle(),
            "pageTemplateHandle" => $pageTemplate->getPageTemplateHandle()
        ]);

        $xml = preg_replace('/<fID>(.*?)<\/fID>/', '<fID>{ccm:export:file:placeholder.jpg}</fID>', $xml);

        $cif = new ContentImporter();
        $cif->importFiles($this->pkg->getPackagePath() . '/content_files', false);
        $cif->importContentString($xml);
    }

    /**
     * @throws Exception
     */
    public function generateText(string $contentDescription): ?string
    {
        return $this->sendPrompt("generate_text", [
            "locale" => Localization::getInstance()->getLocale(),
            "contentDescription" => $contentDescription
        ]);
    }
}