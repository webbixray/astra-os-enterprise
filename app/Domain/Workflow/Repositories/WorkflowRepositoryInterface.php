<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Repositories;

use App\Domain\Workflow\Entities\Workflow;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface WorkflowRepositoryInterface
 *
 * Repository contract for Workflow aggregate root persistence.
 *
 * @package App\Domain\Workflow\Repositories
 */
interface WorkflowRepositoryInterface
{
    /**
     * Find a workflow by its UUID.
     *
     * @param UuidInterface $id
     * @return Workflow|null
     */
    public function findById(UuidInterface $id): ?Workflow;

    /**
     * Find workflows for an organization.
     *
     * @param UuidInterface $organizationId
     * @return array<int, Workflow>
     */
    public function findByOrganizationId(UuidInterface $organizationId): array;

    /**
     * Find workflows for a campaign.
     *
     * @param UuidInterface $campaignId
     * @return array<int, Workflow>
     */
    public function findByCampaignId(UuidInterface $campaignId): array;

    /**
     * Find active workflows.
     *
     * @return array<int, Workflow>
     */
    public function findActive(): array;

    /**
     * Find all workflows.
     *
     * @return array<int, Workflow>
     */
    public function findAll(): array;

    /**
     * Persist a workflow.
     *
     * @param Workflow $workflow
     * @return Workflow
     */
    public function save(Workflow $workflow): Workflow;

    /**
     * Delete a workflow.
     *
     * @param Workflow $workflow
     * @return void
     */
    public function delete(Workflow $workflow): void;
}
