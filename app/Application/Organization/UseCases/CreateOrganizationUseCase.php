<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Organization\DTOs\CreateOrganizationDTO;
use App\Application\Organization\DTOs\OrganizationResponseDTO;
use App\Application\Organization\Services\OrganizationValidationService;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Events\OrganizationCreated;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;

final readonly class CreateOrganizationUseCase
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private OrganizationValidationService $validationService,
    ) {}

    /**
     * Create a new organization.
     *
     * Validates the organization data, checks slug uniqueness,
     * persists the organization, and dispatches the OrganizationCreated event.
     */
    public function execute(CreateOrganizationDTO $dto): OrganizationResponseDTO
    {
        $this->validationService->validateName($dto->name);
        $this->validationService->validateSlug($dto->slug);

        $existing = $this->organizationRepository->findBySlug($dto->slug);
        if ($existing !== null) {
            throw new \InvalidArgumentException("An organization with slug '{$dto->slug}' already exists.");
        }

        $organization = new Organization(
            name: $dto->name,
            slug: $dto->slug,
            ownerId: $dto->ownerId,
            description: $dto->description,
            logo: $dto->logo,
            website: $dto->website,
            settings: $dto->settings,
        );

        $organization = $this->organizationRepository->save($organization);

        // Add owner as admin member
        $this->organizationRepository->addMember($organization->getId(), $dto->ownerId, 'admin');

        OrganizationCreated::dispatch($organization);

        return OrganizationResponseDTO::fromArray($organization->toArray());
    }
}
