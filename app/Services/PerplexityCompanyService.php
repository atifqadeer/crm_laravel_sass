<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PerplexityCompanyService
{
    public function findCompanyData(array $job): array
    {
        $prompt = $this->buildPrompt($job);

        $response = Http::withToken(config('services.perplexity.key'))
            ->acceptJson()
            ->post('https://api.perplexity.ai/chat/completions', [
                'model' => 'sonar-pro',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Return only valid JSON. No markdown, no explanation.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.2,
            ]);

        $response->throw();

        $content = $response->json('choices.0.message.content');

        return $this->safeJsonDecode($content);
    }

    private function buildPrompt(array $job): string
    {
        $companyName = $job['companyName'] ?? $job['source'] ?? 'Unknown Company';
        $city = $job['location']['city'] ?? '';
        $postcode = $job['location']['postalCode'] ?? '';
        $desc = $job['descriptionText'] ?? '';

        return <<<TXT
Find the official contact information for the following UK company.

Company Name: {$companyName}

Requirements:

* Search only official company sources, Companies House records, CQC records (if applicable), and publicly available company contact pages.
* Do not guess or fabricate any information.
* If a field cannot be verified, return null.
* Extract company-level contact information only.
* Prefer direct contact details over generic directory listings.

Return ONLY valid JSON in the following format:

{
"company_name": "",
"website_url": "",
"email": "",
"phone": "",
"landline": "",
"contact_person": "",
"confidence": "high|medium|low",
}

Field Rules:

* phone = primary contact number (mobile or general contact number)
* landline = office landline number
* email = official company email address
* website_url = official company website
* contact_person = directors, registered managers, owners, or publicly listed staff contacts
* confidence = reliability of the extracted data

Return JSON only. No explanations, markdown, or additional text.
TXT;
    }

    private function safeJsonDecode(string $content): array
    {
        $content = trim($content);

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $content = preg_replace('/```json|```/i', '', $content);
        $decoded = json_decode(trim($content), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return [];
    }
}
