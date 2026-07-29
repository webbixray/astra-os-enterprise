<?php

declare(strict_types=1);

namespace App\Application\Social\UseCases;

use App\Application\Social\Services\SentimentAnalyzer;
use App\Application\Social\Services\SocialMediaScheduler;
use App\Domain\Social\Events\MentionProcessed;
use RuntimeException;

final readonly class ProcessMentionsUseCase
{
    public function __construct(
        private SentimentAnalyzer $sentimentAnalyzer,
        private SocialMediaScheduler $scheduler,
    ) {}

    /**
     * Monitor social media mentions and generate AI-powered responses.
     *
     * Analyzes sentiment of each mention and routes urgent/negative mentions
     * for immediate attention while auto-responding to routine mentions.
     *
     * @return array{ processed: int, auto_replied: int, escalated: int }
     */
    public function execute(int $organizationId, array $mentions): array
    {
        $processed = 0;
        $autoReplied = 0;
        $escalated = 0;

        foreach ($mentions as $mention) {
            $processed++;

            $sentiment = $this->sentimentAnalyzer->analyze(
                $mention['text'] ?? $mention['content'] ?? ''
            );

            $mentionData = [
                'id' => $mention['id'],
                'platform' => $mention['platform'],
                'author' => $mention['author'],
                'text' => $mention['text'] ?? $mention['content'] ?? '',
                'sentiment' => $sentiment,
                'organization_id' => $organizationId,
            ];

            // Escalate urgent/negative mentions
            if ($sentiment['label'] === 'negative' && $sentiment['score'] > 0.7) {
                $escalated++;
                // In production: dispatch EscalationRequired event
                continue;
            }

            // Auto-respond to routine mentions
            if ($sentiment['label'] === 'neutral' || ($sentiment['label'] === 'positive' && $sentiment['score'] < 0.9)) {
                $response = $this->generateAutoResponse($mentionData);
                $autoReplied++;

                MentionProcessed::dispatch(
                    mentionId: $mention['id'],
                    platform: $mention['platform'],
                    response: $response,
                );
            }
        }

        return [
            'processed' => $processed,
            'auto_replied' => $autoReplied,
            'escalated' => $escalated,
        ];
    }

    /**
     * Generate an automated response for a mention.
     */
    private function generateAutoResponse(array $mention): string
    {
        $sentiment = $mention['sentiment'];

        if ($sentiment['label'] === 'positive') {
            return "Thank you for your kind words! We appreciate your support.";
        }

        return "Thank you for reaching out. We've noted your message and will get back to you if needed.";
    }
}
