<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Organization\Entities\Organization as OrganizationEntity;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Infrastructure\Persistence\Models\Organization as OrganizationModel;
use DateTimeImmutable;

final class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    /**
     * Find an organization by its ID.
     */
    public function findById(int $id): ?OrganizationEntity
    {
        $model = OrganizationModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Find organizations by owner ID.
     *
     * @return OrganizationEntity[]
     */
    public function findByOwnerId(int $ownerId): array
    {
        return OrganizationModel::where('owner_id', $ownerId)
            ->get()
            ->map(fn (OrganizationModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find an organization by its slug.
     */
    public function findBySlug(string $slug): ?OrganizationEntity
    {
        $model = OrganizationModel::where('slug', $slug)->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Save an organization entity.
     */
    public function save(OrganizationEntity $organization): OrganizationEntity
    {
        $data = [
            'name' => $organization->getName(),
            'slug' => $organization->getSlug(),
            'description' => $organization->getDescription(),
            'logo' => $organization->getLogo(),
            'website' => $organization->getWebsite(),
            'settings' => $organization->getSettings(),
            'owner_id' => $organization->getOwnerId(),
        ];

        if ($organization->getId() !== null) {
            OrganizationModel::where('id', $organization->getId())->update($data);
            $model = OrganizationModel::find($organization->getId());
        } else {
            $model = OrganizationModel::create($data);
        }

        if ($model === null) {
            throw new \RuntimeException('Failed to save organization.');
        }

        $organization->setId($model->id);

        return $organization;
    }

    /**
     * Delete an organization.
     */
    public function delete(int $id): bool
    {
        return (bool) OrganizationModel::where('id', $id)->delete();
    }

    /**
     * Add a member to an organization.
     */
    public function addMember(int $organizationId, int $userId, string $role): void
    {
        $model = OrganizationModel::findOrFail($organizationId);
        $model->members()->attach($userId, ['role' => $role]);
    }

    /**
     * Remove a member from an organization.
     */
    public function removeMember(int $organizationId, int $userId): void
    {
        $model = OrganizationModel::findOrFail($organizationId);
        $model->members()->detach($userId);
    }

    /**
     * Get members of an organization.
     *
     * @return array<int, array{id: int, name: string, email: string, role: string}>
     */
    public function getMembers(int $organizationId): array
    {
        $model = OrganizationModel::findOrFail($organizationId);

        return $model->members()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ])
            ->all();
    }

    /**
     * Convert an Eloquent model to a domain entity.
     */
    private function toEntity(OrganizationModel $model): OrganizationEntity
    {
        $entity = new OrganizationEntity(
            name: $model->name,
            slug: $model->slug,
            ownerId: $model->owner_id,
            description: $model->description,
            logo: $model->logo,
            website: $model->website,
            settings: $model->settings ?? [],
        );

        $entity->setId($model->id);

        return $entity;
    }
}
