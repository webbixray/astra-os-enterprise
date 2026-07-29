<?php

declare(strict_types=1);

namespace App\Application\Agent\Services;

use RuntimeException;

final class ModelRouterService
{
    private const array PROVIDER_CHAIN = [
        'openai' => ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo'],
        'anthropic' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
        'google' => ['gemini-pro', 'gemini-flash'],
    ];

    /**
     * Route a prompt to the appropriate AI model with fallback chain.
     *
     * @throws RuntimeException if all providers in the chain fail.
     */
    public function route(string $model, array $prompt, array $systemContext = []): array
    {
        $provider = $this->resolveProvider($model);

        if ($provider === null) {
            throw new RuntimeException("No provider found for model '{$model}'.");
        }

        $modelChain = self::PROVIDER_CHAIN[$provider] ?? [$model];
        $lastException = null;

        foreach ($modelChain as $fallbackModel) {
            try {
                return $this->callProvider($provider, $fallbackModel, $prompt, $systemContext);
            } catch (RuntimeException $e) {
                $lastException = $e;
                continue;
            }
        }

        throw new RuntimeException(
            sprintf(
                'All provider attempts failed for model "%s". Last error: %s',
                $model,
                $lastException?->getMessage() ?? 'Unknown error'
            )
        );
    }

    private function resolveProvider(string $model): ?string
    {
        foreach (self::PROVIDER_CHAIN as $provider => $models) {
            if (in_array($model, $models, true)) {
                return $provider;
            }
        }

        return null;
    }

    private function callProvider(string $provider, string $model, array $prompt, array $systemContext): array
    {
        return [
            'provider' => $provider,
            'model' => $model,
            'content' => sprintf(
                'Simulated response from %s/%s for agent role: %s',
                $provider,
                $model,
                $systemContext['agent_role'] ?? 'unknown'
            ),
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }
}
