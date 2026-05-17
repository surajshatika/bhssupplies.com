<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class ReadabilityService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $content   = $payload['content'] ?? '';
        $keyword   = $payload['keyword'] ?? '';
        $wordCount = $this->wordCount($content);

        $prompt = <<<PROMPT
Analyze the readability of the following content and provide improvement suggestions.

Focus Keyword: {$keyword}
Word Count: {$wordCount}
Content:
{$content}

Evaluate: sentence length, paragraph length, passive voice, transition words, Flesch reading ease.

Return JSON:
{
  "flesch_reading_ease": 0.0,
  "reading_level": "Easy|Fairly Easy|Standard|Fairly Difficult|Difficult",
  "avg_sentence_length": 0,
  "avg_paragraph_length": 0,
  "passive_voice_percent": 0,
  "transition_words_percent": 0,
  "long_sentences": [],
  "issues": [],
  "improved_intro": "",
  "recommendations": [],
  "score": 0,
  "grade": "A"
}
PROMPT;

        return $this->askForJson($prompt, 'You are a content readability and UX specialist.', [
            'flesch_reading_ease'      => 0.0,
            'reading_level'            => 'Standard',
            'avg_sentence_length'      => 0,
            'avg_paragraph_length'     => 0,
            'passive_voice_percent'    => 0,
            'transition_words_percent' => 0,
            'long_sentences'           => [],
            'issues'                   => [],
            'improved_intro'           => '',
            'recommendations'          => [],
            'score'                    => 0,
            'grade'                    => 'C',
        ]);
    }
}
