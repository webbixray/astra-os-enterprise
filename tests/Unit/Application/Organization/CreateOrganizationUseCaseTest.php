<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Organization;

use App\Application\Organization\DTOs\CreateOrganizationDTO;
use App\Application\Organization\DTOs\OrganizationResponseDTO;
use App\Application\Organization\Services\OrganizationValidationService;
use App\Application\Organization\UseCases\CreateOrganizationUseCase;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Events\OrganizationCreated;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateOrganizationUseCase.
 *
 * Verifies organization creation logic with a mocked repository
 * and validation service.
 */
final class CreateOrganizationUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Creates an organization with owner member and returns an OrganizationResponseDTO.
     */
    public function test_creates_organization_with_owner_member(): void
    {
        $mockOrgRepo = Mockery::mock(OrganizationRepositoryInterface::class);
        $mockValidationService = Mockery::mock(OrganizationValidationService::class);
        $eventMock = Mockery::mock('overload:' . OrganizationCreated::class);

        $dto = new CreateOrganizationDTO(
            name: 'Acme Corp',
            slug: 'acme-corp',
            ownerId: 10,
            description: 'A leading technology company',
            website: 'https://acme.example.com',
        );

        $mockValidationService->expects()->validateName($dto->name);
        $mockValidationService->expects()->validateSlug($dto->slug);

        $mockOrgRepo
            ->expects()
            ->findBySlug('acme-corp')
            ->andReturn(null);

        $mockOrgRepo
            ->expects()
            ->save(Mockery::type(Organization::class))
            ->andReturnUsing(function (Organization $org) {
                $org->setId(1);
                return $org;
            });

        $mockOrgRepo
            ->expects()
            ->addMember(1, 10, 'admin');

        $eventMock->expects()->dispatch(Mockery::type(Organization::class));

        $useCase = new CreateOrganizationUseCase($mockOrgRepo, $mockValidationService);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(OrganizationResponseDTO::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('Acme Corp', $result->name);
        $this->assertSame('acme-corp', $result->slug);
        $this->assertSame(10, $result->ownerId);
    }

    /**
     * Throws an InvalidArgumentException when the slug already exists.
     */
    public function test_throws_on_duplicate_slug(): void
    {
        $mockOrgRepo = Mockery::mock(OrganizationRepositoryInterface::class);
        $mockValidationService = Mockery::mock(OrganizationValidationService::class);

        $existingOrg = new Organization(
            name: 'Existing Org',
            slug: 'duplicate-slug',
            ownerId: 5,
        );
        $existingOrg->setId(99);

        $dto = new CreateOrganizationDTO(
            name: 'New Org',
            slug: 'duplicate-slug',
            ownerId: 7,
        );

        $mockValidationService->expects()->validateName($dto->name);
        $mockValidationService->expects()->validateSlug($dto->slug);

        $mockOrgRepo
            ->expects()
            ->findBySlug('duplicate-slug')
            ->andReturn($existingOrg);

        $useCase = new CreateOrganizationUseCase($mockOrgRepo, $mockValidationService);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("An organization with slug 'duplicate-slug' already exists.");

        $useCase->execute($dto);
    }

    /**
     * Dispatches the OrganizationCreated domain event after successful creation.
     */
    public function test_dispatches_organization_created_event(): void
    {
        $mockOrgRepo = Mockery::mock(OrganizationRepositoryInterface::class);
        $mockValidationService = Mockery::mock(OrganizationValidationService::class);
        $eventMock = Mockery::mock('overload:' . OrganizationCreated::class);

        $dto = new CreateOrganizationDTO(
            name: 'Startup Inc',
            slug: 'startup-inc',
            ownerId: 3,
        );

        $mockValidationService->expects()->validateName($dto->name);
        $mockValidationService->expects()->validateSlug($dto->slug);

        $mockOrgRepo
            ->expects()
            ->findBySlug('startup-inc')
            ->andReturn(null);

        $mockOrgRepo
            ->expects()
            ->save(Mockery::type(Organization::class))
            ->andReturnUsing(function (Organization $org) {
                $org->setId(5);
                return $org;
            });

        $mockOrgRepo
            ->expects()
            ->addMember(5, 3, 'admin');

        $eventMock->expects()->dispatch(Mockery::type(Organization::class));

        $useCase = new CreateOrganizationUseCase($mockOrgRepo, $mockValidationService);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(OrganizationResponseDTO::class, $result);
        $this->assertSame(5, $result->id);
    }
}
