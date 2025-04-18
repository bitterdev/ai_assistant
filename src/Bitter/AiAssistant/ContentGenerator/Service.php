<?php /** @noinspection PhpUnused */

namespace Bitter\AiAssistant\ContentGenerator;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Block\Block;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\Page\Template;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;

class Service implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    protected Repository $config;
    protected Client $client;
    protected Connection $db;

    public function __construct(
        Repository $config,
        Connection $db
    )
    {
        $this->config = $config;
        $this->db = $db;

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 30.0,
        ]);
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

        $payload = json_encode([
            "pageData" => $pageData
        ]);

        $prompt = <<<PROMPT
You will receive a JSON object called "pageData". It contains a list of pages, each with a "cID" (page ID) and an array of "blocks" that include HTML content and other technical data.

Your task is to:

1. Loop through all pages.
2. Extract the visible text content from all HTML blocks.
3. Detect the language of the text.
4. Analyze the semantic content of each page.
5. Generate SEO metadata for each page:
   - title: A concise title summarizing the page content (~60 characters).
   - description: A compelling meta description (~160 characters).
   - keywords: A list of relevant keywords separated by commas.

Return the result in the following JSON schema:

{
  "pageData": [
    {
      "cID": 1,
      "language": "de",
      "seo": {
        "title": "Example Title",
        "description": "Short, compelling description of the page content.",
        "keywords": "keyword1, keyword2, keyword3"
      }
    },
    {
      "cID": 2,
      "language": "en",
      "seo": {
        "title": "Another Sample Title",
        "description": "Another engaging description here.",
        "keywords": "keywordA, keywordB, keywordC"
      }
    }
  ]
}

Important:
- Must return valid JSON without line breaks or white-space
- The "cID" must match exactly between input and output to ensure correct assignment.
- Provide an SEO object for every page, even if content is minimal or missing.
- Use the language detected from the extracted text as the "language" value.

Input example:
{
  "pageData": [
    {
      "cID": 1,
      "blocks": [ /* HTML content blocks */ ]
    },
    {
      "cID": 2,
      "blocks": [ /* HTML content blocks */ ]
    }
  ]
}

Input:
$payload
PROMPT;

        $json = $this->sendPrompt($prompt);

        $r = json_decode($json, true);

        if (is_array($r) &&
            isset($r["pageData"]) &&
            is_array($r["pageData"])) {

            foreach ($r["pageData"] as $pageData) {
                if (isset($pageData["cID"]) &&
                    isset($pageData["seo"]) &&
                    is_array($pageData["seo"]) &&
                    isset($pageData["seo"]["title"]) &&
                    isset($pageData["seo"]["description"]) &&
                    isset($pageData["seo"]["keywords"])) {

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
                } else {
                    throw new Exception(t("Invalid schema."));
                }
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

        $payload = json_encode([
            "pageData" => $pageData
        ]);

        $prompt = <<<PROMPT
You will receive a JSON object called "pageData". Each entry has a "cID" (page ID) and a "blocks" array. Each block contains technical data and HTML or text content.

Your task is to:

1. Loop through all pages and blocks.
2. Keep the structure 100% unchanged – do not change any keys or remove any elements.
3. Translate all human-readable text values (e.g., content inside "html", "text", "label", etc.).
4. Preserve the original formatting (e.g., valid HTML structure, whitespace).
5. Leave all non-text values (e.g., numbers, IDs, booleans) untouched.
6. Ensure that every input page with its "cID" is present in the output with the same "cID".

Important:
- Must return valid JSON without line breaks or white-space
- Do not change any keys or structural elements.
- The output must be fully valid JSON and exactly match the input format, except for the translated text content.
- Only translate actual text. Do not translate keys or technical data.

Input:
{
  "pageData": [
    {
      "cID": 1,
      "blocks": [
        { "html": "<h1>Willkommen auf unserer Webseite</h1><p>Wir bieten Lösungen.</p>" },
        { "label": "Mehr erfahren", "type": "button" }
      ]
    },
    {
      "cID": 2,
      "blocks": [
        { "html": "<p>Kontaktieren Sie uns für weitere Informationen.</p>" }
      ]
    }
  ]
}

Expected Output:
{
  "pageData": [
    {
      "cID": 1,
      "blocks": [
        { "html": "<h1>Welcome to our website</h1><p>We offer solutions.</p>" },
        { "label": "Learn more", "type": "button" }
      ]
    },
    {
      "cID": 2,
      "blocks": [
        { "html": "<p>Contact us for more information.</p>" }
      ]
    }
  ]
}

Input:
$payload
PROMPT;

        //$json = $this->sendPrompt($prompt);

        $json = <<<EOL
{
  "pageData": [
    {
      "cID": 2630,
      "blocks": {
        "7832": {
          "bID": 7832,
          "content": "<meta charset=\"UTF-8\">\r\n<title><\/title>\r\n<h1>Rabbits<\/h1>\r\n\r\n<h2>General Information about Rabbits:<\/h2>\r\n\r\n<ul>\r\n\t<li>Rabbits belong to the family Leporidae.<\/li>\r\n\t<li>They are mainly known for their long ears and fast movement.<\/li>\r\n<\/ul>\r\n\r\n<h2>Habitat:<\/h2>\r\n\r\n<p>Rabbits live in various habitats including forests, meadows, and fields.<\/p>\r\n\r\n<h2>Nutrition:<\/h2>\r\n\r\n<p>Rabbits are herbivores and mainly feed on grasses, herbs, and roots.<\/p>\r\n\r\n<h2>Reproduction:<\/h2>\r\n\r\n<ul>\r\n\t<li>Female rabbits are called does.<\/li>\r\n\t<li>Rabbits have a short gestation period and usually give birth to multiple offspring.<\/li>\r\n<\/ul>\r\n"
        },
        "7835": {
          "bID": 7835,
          "arLayoutID": 8
        },
        "7834": {
          "bID": 7834,
          "icon": "",
          "title": "I am a Text",
          "paragraph": "<h2>Male Rabbits<\/h2>\r\n\r\n<p>Male rabbits are called bucks and are an important part of rabbit life. Here are some interesting facts about male rabbits:<\/p>\r\n\r\n<ul>\r\n\t<li>Bucks are usually larger than female rabbits.<\/li>\r\n\t<li>They have distinctive features like longer ears and stronger jaws.<\/li>\r\n\t<li>Male rabbits play a crucial role in reproduction and den protection.<\/li>\r\n\t<li>They are territorial and aggressively defend their territory against intruders.<\/li>\r\n<\/ul>\r\n",
          "externalLink": "",
          "internalLinkCID": 0,
          "titleFormat": "h4",
          "fID": 0
        },
        "7836": {
          "bID": 7836,
          "icon": "",
          "title": "I am a Text",
          "paragraph": "<meta charset=\"UTF-8\">\r\n<title><\/title>\r\n<h1>Female Rabbits<\/h1>\r\n\r\n<p>Rabbits are cute and popular animals, especially female rabbits have some interesting characteristics:<\/p>\r\n\r\n<h2>Appearance<\/h2>\r\n\r\n<ul>\r\n\t<li>Female rabbits are usually slightly smaller than male rabbits.<\/li>\r\n\t<li>They often have a finer build and are more elegant.<\/li>\n<\/ul>\n\n<h2>Behavior<\/h2>\n\n<ul>\n\t<li>Female rabbits are usually more social than male rabbits.<\/li>\n\t<li>They care lovingly for their young and show protective behavior.<\/li>\n<\/ul>\n\n<h2>Reproduction<\/h2>\n\n<ul>\n\t<li>Female rabbits have a gestation period of about 30 days and give birth to multiple offspring.<\/li>\n\t<li>They are known for their high reproduction rate.<\/li>\n<\/ul>\n",
          "externalLink": "",
          "internalLinkCID": 0,
          "titleFormat": "h4",
          "fID": 0
        }
      }
    }
  ]
}
EOL;

        $r = json_decode($json, true);

        // @todo: fix me

        var_dump($json);

        die();
    }

    /**
     * @throws Exception
     */
    public function generatePage(
        Page     $parentPage,
        Template $pageTemplate,
        Type     $pageType,
        string   $pageName,
        string   $input
    ): string
    {
        $u = new User();
        $locale = Localization::getInstance()->getLocale();
        $userPrompt = $input;
        $parentPagePath = $parentPage->getCollectionPath();
        $userName = $u->getUserName();

        $prompt = <<<EOT
You are an expert in generating XML content in the Concrete CMS Import Format (CIF version 1.0). Your job is to generate complete and valid CIF XML files for web pages based on the user's instructions.

Please strictly follow this structure:

- The root element must be: <?xml version="1.0" encoding="UTF-8"?><concrete5-cif version="1.0">
- Include one <page> element below <pages> with the following attributes:
  - name: The page name, based on user input
  - path: Auto-generate based on the name (e.g. "/my-page") below parent page base provided by user
  - public-date: Use current date/time (or fixed demo date)
  - pagetype: Use the value provided by the user
  - template: Use the value provided by the user
  - user: Use the value provided by the user
  - root="true"
- Inside the <page>, create one <area name="Main"> with one or more <block> elements.

You may use the following block types:
- page_title
- content (btContentLocal)
- feature_link
- form
- youtube
- faq
- image (btContentImage, use placeholder {ccm:export:file:placeholder.jpg})
- core_area_layout with arealayout type="theme-grid" and nested <columns> with <column span="..."> each containing blocks

Blocks can be **nested** via columns inside layouts. Feel free to mix multiple block types. All content must be wrapped in CDATA.

The output must be valid XML and must not contain any explanation or extra text. Output only the raw XML, nothing else.

Here is the user input:
- Parent Page Path: $parentPagePath
- Page Name: $pageName
- User: $userName
- Page Type: {$pageType->getPageTypeHandle()}
- Page Template: {$pageTemplate->getPageTemplateHandle()}
- Locale: $locale
- Content description: "$userPrompt"

Now generate the full XML in CIF format.
EOT;

        return $this->sendPrompt($prompt);
    }

    /**
     * @throws Exception
     */
    public function generateText(string $input): ?string
    {
        $locale = Localization::getInstance()->getLocale();

        $prompt = <<<EOT
You are a professional web content writer. Your task is to create high-quality, engaging, and SEO-friendly content formatted in HTML.

Here is the user's description of the content they want for their website:
"$input"

Please follow these rules:
- Respond strictly in HTML format (e.g. use <h2>, <p>, <ul>, <strong>, etc.)
- Write in the language that matches the following locale: $locale
- Use short, structured paragraphs with subheadings
- Include bullet points or highlights where appropriate
- Do not include any explanatory text or introductions – output only pure HTML content
- No Markdown, no plain text – just valid, semantic HTML

Start directly with the HTML output.
EOT;

        return $this->sendPrompt($prompt);
    }
}