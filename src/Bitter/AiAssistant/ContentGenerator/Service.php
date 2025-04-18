<?php /** @noinspection PhpUnused */

namespace Bitter\AiAssistant\ContentGenerator;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Block\Block;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\Page\Template;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\User\User;
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

    public function __construct(
        Repository     $config,
        Connection     $db,
        PackageService $packageService
    )
    {
        $this->config = $config;
        $this->db = $db;

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 60.0,
        ]);

        $this->packageService = $packageService;
        $pkgEntity = $this->packageService->getByHandle("ai_assistant");
        $this->pkg = $pkgEntity->getController();
    }

    /**
     * @throws Exception
     */
    protected function validateSchema(string $schemaFile, array $json): bool
    {
        $file = $this->pkg->getPackagePath() . "/schemas/" . $schemaFile;
        $schema = json_decode(file_get_contents($file));

        $validator = new Validator();
        $validator->validate($json, $schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);

        if ($validator->isValid()) {
            return true;
        } else {
            foreach ($validator->getErrors() as $error) {
                throw new Exception($error['message']);
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected function buildPrompt(string $templateFile, array $params = []): string
    {
        ob_start();
        View::element($templateFile, $params, "ai_assistant");
        $prompt = ob_get_contents();
        ob_end_clean();
        return $prompt;
    }

    /**
     * @throws Exception
     */
    protected function sendPrompt(string $prompt): ?string
    {
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

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['choices'][0]['message']['content'] ?? null;
    }

    /**
     * @throws Exception
     */
    protected function parseResponse(?string $r): ?array
    {
        // Remove invalid control characters
        $json = preg_replace('/[[:cntrl:]]/', '', $r);

        // Enforce UTF-8
        $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');

        $r = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg());
        }

        return $r;
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
     * @throws Exception
     *
     * @var array|Page[] $pages
     */
    public function optimizeSeoForMultiplePages(
        array $pages
    ): void
    {
        $pageData = [];

        foreach ($pages as $page) {
            $pageData[] = [
                "cID" => $page->getCollectionID(),
                "blocks" => $this->collectBlockData($page)
            ];
        }

        $prompt = $this->buildPrompt("prompts/seo", [
            "payload" => json_encode([
                "pageData" => $pageData
            ])
        ]);

        $response = $this->sendPrompt($prompt);

        $parsedResponse = $this->parseResponse($response);

        $this->validateSchema("seo-response.json", $parsedResponse);

        foreach ($parsedResponse["pageData"] as $pageData) {
            $cID = $pageData["cID"];
            $title = $pageData["seo"]["title"];
            $description = $pageData["seo"]["description"];
            $keywords = $pageData["seo"]["keywords"];

            $c = Page::getByID($cID);

            if ($c instanceof Page) {
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
        $pageData = [];

        foreach ($pages as $page) {
            $pageData[] = [
                "cID" => $page->getCollectionID(),
                "blocks" => $this->collectBlockData($page)
            ];
        }

        $prompt = $this->buildPrompt("prompts/translate", [
            "payload" => json_encode([
                "pageData" => $pageData
            ]),
            "targetLocale" => $targetLocale
        ]);

        $response = $this->sendPrompt($prompt);

        $parsedResponse = $this->parseResponse($response);

        $this->validateSchema("translate-response.json", $parsedResponse);

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
    ): string
    {
        $u = new User();

        $prompt = $this->buildPrompt("prompts/generate_page", [
            "locale" => Localization::getInstance()->getLocale(),
            "parentPagePath" => $parentPage->getCollectionPath(),
            "userName" => $u->getUserName(),
            "pageName" => $pageName,
            "contentDescription" => $contentDescription,
            "pageTypeHandle" => $pageType->getPageTypeHandle(),
            "pageTemplateHandle" => $pageTemplate->getPageTemplateHandle()
        ]);

        // @todo: implement content parser

        return $this->sendPrompt($prompt);
    }

    /**
     * @throws Exception
     */
    public function generateText(string $contentDescription): ?string
    {
        $prompt = $this->buildPrompt("prompts/generate_text", [
            "locale" => Localization::getInstance()->getLocale(),
            "contentDescription" => $contentDescription
        ]);

        return $this->sendPrompt($prompt);
    }
}