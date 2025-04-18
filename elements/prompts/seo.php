<?php

/** @var string $payload */

echo <<<PROMPT
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