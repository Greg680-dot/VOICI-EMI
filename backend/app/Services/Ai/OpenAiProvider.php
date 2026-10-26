<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    public function analyze(string $content, string $type): array
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API Key is empty. Returning mock response for analysis.');
            
            $spamKeywords = ['gagner', 'argent', 'gratuit', 'cliquez', 'urgent', 'miracle', 'secret'];
            $foundCount = 0;
            foreach ($spamKeywords as $word) {
                if (stripos($content, $word) !== false) {
                    $foundCount++;
                }
            }
            
            $score = max(0, 100 - ($foundCount * 15));
            if ($score < 50) {
                $rating = "Probabilité élevée de fausse information ou de tentative de phishing.";
            } elseif ($score < 75) {
                $rating = "Contenu à nuancer, comporte des mots-clés typiquement sensationnalistes.";
            } else {
                $rating = "Le contenu semble neutre ou informatif, mais nécessite une vérification humaine pour confirmer.";
            }

            return [
                'score' => (float)$score,
                'analysis' => "[ANALYSE SIMULÉE - HORS LIGNE]\n\nType de contenu: $type\n\nÉvaluation:\n$rating\n\n(Note: configurez la clé OPENAI_API_KEY dans votre fichier .env pour activer les appels réels de l'API OpenAI)."
            ];
        }

        try {
            $prompt = "Vous êtes un expert en vérification des faits (fact-checking) pour VoiCI-EMI au Bénin.
Analysez le contenu suivant et déterminez son niveau de fiabilité et de crédibilité.
Type de contenu : {$type}
Contenu :
{$content}

Vous devez renvoyer STRICTEMENT une réponse au format JSON contenant uniquement les clés suivantes :
- \"score\" : un nombre décimal entre 0 et 100 représentant l'indice de crédibilité (100 = totalement vrai, 0 = totalement faux ou trompeur).
- \"analysis\" : un texte explicatif (en français) résumant les arguments, les biais détectés, et les points de vigilance.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
            ]);

            if ($response->failed()) {
                throw new \Exception("OpenAI API call failed: " . $response->body());
            }

            $data = $response->json();
            $rawJson = $data['choices'][0]['message']['content'] ?? '{}';
            $result = json_decode($rawJson, true);

            $score = $result['score'] ?? 50.0;
            $analysis = $result['analysis'] ?? 'Impossible de récupérer l\'analyse détaillée de l\'IA.';

            return [
                'score' => (float)$score,
                'analysis' => $analysis,
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI Analysis failed: ' . $e->getMessage());
            return [
                'score' => 50.0,
                'analysis' => "Une erreur s'est produite lors de l'analyse par l'IA : " . $e->getMessage() . "\nUn score de 50 a été attribué par défaut.",
            ];
        }
    }
}
