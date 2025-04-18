<?php

/** @var string $payload */
/** @var string $targetLocale */

echo <<<PROMPT
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
- Target language/Locale: $targetLocale

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