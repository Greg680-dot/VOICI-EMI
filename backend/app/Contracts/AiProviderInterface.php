<?php

namespace App\Contracts;

interface AiProviderInterface
{
    /**
     * Analyze content for credibility.
     *
     * @param string $content The text content to analyze.
     * @param string $type The content type (text, link, media, etc.).
     * @return array Array containing 'score' (0-100 decimal) and 'analysis' (string explanation).
     */
    public function analyze(string $content, string $type): array;
}
