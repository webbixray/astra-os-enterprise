<?php

declare(strict_types=1);

namespace App\Application\Workflow\Services;

use App\Domain\Workflow\Entities\Workflow;
use RuntimeException;

final class WorkflowEngineService
{
    /**
     * Execute a workflow by processing its node graph.
     *
     * @return array{ nodes_executed: int, node_results: array }
     */
    public function execute(Workflow $workflow, array $inputVariables = []): array
    {
        $nodes = $workflow->getNodes();
        $edges = $workflow->getEdges();
        $variables = array_merge($workflow->getVariables(), $inputVariables);

        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        $adjacencyList = [];
        $inDegree = [];
        foreach ($nodes as $node) {
            $adjacencyList[$node['id']] = [];
            $inDegree[$node['id']] = 0;
        }
        foreach ($edges as $edge) {
            $adjacencyList[$edge['from']][] = $edge['to'];
            $inDegree[$edge['to']]++;
        }

        $startNodes = [];
        foreach ($nodes as $node) {
            if ($node['type'] === 'trigger' || $inDegree[$node['id']] === 0) {
                $startNodes[] = $node['id'];
            }
        }

        if (empty($startNodes)) {
            throw new RuntimeException('Workflow has no start nodes. Ensure at least one trigger node exists.');
        }

        $nodeResults = [];
        $executionOrder = $this->topologicalSort($nodeMap, $adjacencyList, $inDegree);

        foreach ($executionOrder as $nodeId) {
            $node = $nodeMap[$nodeId];

            $result = match ($node['type']) {
                'trigger' => $this->executeTrigger($node, $variables),
                'action' => $this->executeAction($node, $variables),
                'condition' => $this->evaluateCondition($node, $variables),
                'output' => $this->executeOutput($node, $variables),
                default => throw new RuntimeException("Unknown node type: {$node['type']}"),
            };

            $nodeResults[$nodeId] = $result;

            if (isset($node['output_variable']) && isset($result['value'])) {
                $variables[$node['output_variable']] = $result['value'];
            }

            if ($node['type'] === 'condition' && isset($result['branch'])) {
                $variables['__branch'] = $result['branch'];
            }
        }

        return [
            'nodes_executed' => count($nodeResults),
            'node_results' => $nodeResults,
        ];
    }

    private function topologicalSort(array $nodeMap, array $adjacencyList, array $inDegree): array
    {
        $sorted = [];
        $queue = [];

        foreach ($inDegree as $nodeId => $degree) {
            if ($degree === 0) {
                $queue[] = $nodeId;
            }
        }

        while (!empty($queue)) {
            $current = array_shift($queue);
            $sorted[] = $current;

            foreach ($adjacencyList[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }

        return $sorted;
    }

    private function executeTrigger(array $node, array $variables): array
    {
        return [
            'type' => 'trigger',
            'status' => 'passed',
            'value' => $node['config']['event'] ?? 'manual',
        ];
    }

    private function executeAction(array $node, array $variables): array
    {
        $actionType = $node['config']['action_type'] ?? 'unknown';
        $params = $node['config']['params'] ?? [];

        $resolvedParams = $this->resolveVariables($params, $variables);

        return [
            'type' => 'action',
            'action' => $actionType,
            'status' => 'completed',
            'value' => $resolvedParams,
        ];
    }

    private function evaluateCondition(array $node, array $variables): array
    {
        $expression = $node['config']['expression'] ?? 'true';
        $resolved = $this->resolveVariables(['expression' => $expression], $variables);

        $result = $resolved['expression'] === 'true' || $resolved['expression'] === true;

        return [
            'type' => 'condition',
            'status' => 'evaluated',
            'value' => $result,
            'branch' => $result ? 'true' : 'false',
        ];
    }

    private function executeOutput(array $node, array $variables): array
    {
        return [
            'type' => 'output',
            'status' => 'completed',
            'value' => $node['config']['output'] ?? null,
        ];
    }

    private function resolveVariables(array $params, array $variables): array
    {
        $resolved = [];

        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $resolved[$key] = preg_replace_callback(
                    '/\{\{\s*(\w+)\s*\}\}/',
                    fn ($matches) => $variables[$matches[1]] ?? $matches[0],
                    $value
                );
            } elseif (is_array($value)) {
                $resolved[$key] = $this->resolveVariables($value, $variables);
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }
}
