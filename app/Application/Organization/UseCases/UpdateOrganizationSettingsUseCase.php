<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Organization\DTOs\OrganizationResponseDTO;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use RuntimeException;

final readonly class UpdateOrganizationSettingsUseCase
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {}

    /**
     * Update organization settings.
     *
     * @throws RuntimeException if organization is not found.
     */
    public function execute(int $organizationId, array $settings): OrganizationResponseDTO
    {
        $organization = $this->organizationRepository->findById($organizationId);

        if ($organization === null) {
            throw new RuntimeException("Organization with ID {$organizationId} not found.");
        }

        $organization->updateSettings($settings);
        $organization = $this->organizationRepository->save($organization);

        return OrganizationResponseDTO::fromArray($organization->toArray());
    }
}
