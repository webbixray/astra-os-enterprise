<?php

declare(strict_types=1);

namespace App\Application\Social\Services;

final class SentimentAnalyzer
{
    /** @var string[] Positive keywords */
    private const array POSITIVE_KEYWORDS = [
        'great', 'amazing', 'excellent', 'love', 'wonderful', 'fantastic',
        'awesome', 'brilliant', 'outstanding', 'superb', 'happy', 'thank',
        'grateful', 'impressed', 'perfect', 'best', 'incredible',
    ];

    /** @var string[] Negative keywords */
    private const array NEGATIVE_KEYWORDS = [
        'bad', 'terrible', 'awful', 'hate', 'horrible', 'worst',
        'disappointed', 'frustrating', 'useless', 'broken', 'fail',
        'poor', 'angry', 'annoying', 'ridiculous', 'waste',
    ];

    /** @var string[] Intensifiers that amplify sentiment */
    private const array INTENSIFIERS = [
        'very', 'extremely', 'incredibly', 'absolutely', 'completely',
        'totally', 'really', 'so', 'too', 'highly',
    ];

    /**
     * Analyze the sentiment of a text.
     *
     * @return array{ label: string, score: float, details: array }
     */
    public function analyze(string $text): array
    {
        $text = mb_strtolower(trim($text));
        $words = str_word_count($text, 1);
        $totalWords = count($words);

        if ($totalWords === 0) {
            return [
                'label' => 'neutral',
                'score' => 0.0,
                'details' => ['positive_words' => 0, 'negative_words' => 0, 'total_words' => 0],
            ];
        }

        $positiveCount = 0;
        $negativeCount = 0;
        $intensifiedPositive = 0;
        $intensifiedNegative = 0;

        foreach ($words as $i => $word) {
            if (in_array($word, self::POSITIVE_KEYWORDS, true)) {
                $positiveCount++;
                // Check for preceding intensifier
                if ($i > 0 && in_array($words[$i - 1], self::INTENSIFIERS, true)) {
                    $intensifiedPositive++;
                }
            }

            if (in_array($word, self::NEGATIVE_KEYWORDS, true)) {
                $negativeCount++;
                if ($i > 0 && in_array($words[$i - 1], self::INTENSIFIERS, true)) {
                    $intensifiedNegative++;
                }
            }
        }

        // Calculate weighted score
        $positiveScore = ($positiveCount + $intensifiedPositive * 0.5) / $totalWords;
        $negativeScore = ($negativeCount + $intensifiedNegative * 0.5) / $totalWords;

        $netScore = $positiveScore - $negativeScore;

        // Determine label
        $label = match (true) {
            $netScore > 0.15 => 'positive',
            $netScore < -0.15 => 'negative',
            default => 'neutral',
        };

        return [
            'label' => $label,
            'score' => round(abs($netScore), 4),
            'details' => [
                'positive_words' => $positiveCount,
                'negative_words' => $negativeCount,
                'intensified_positive' => $intensifiedPositive,
                'intensified_negative' => $intensifiedNegative,
                'total_words' => $totalWords,
            ],
        ];
    }
}
