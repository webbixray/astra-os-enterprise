<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Social\Entities\SocialPost as SocialPostEntity;
use App\Domain\Social\Repositories\SocialPostRepositoryInterface;
use App\Infrastructure\Persistence\Models\SocialPost as SocialPostModel;
use DateTimeImmutable;

final class EloquentSocialPostRepository implements SocialPostRepositoryInterface
{
    /**
     * Find a post by its ID.
     */
    public function findById(int $id): ?SocialPostEntity
    {
        $model = SocialPostModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Find posts by organization ID.
     *
     * @return SocialPostEntity[]
     */
    public function findByOrganizationId(int $organizationId): array
    {
        return SocialPostModel::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (SocialPostModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find posts by status.
     *
     * @return SocialPostEntity[]
     */
    public function findByStatus(string $status): array
    {
        return SocialPostModel::where('status', $status)
            ->get()
            ->map(fn (SocialPostModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find posts by platform.
     *
     * @return SocialPostEntity[]
     */
    public function findByPlatform(string $platform): array
    {
        return SocialPostModel::whereJsonContains('platforms', $platform)
            ->get()
            ->map(fn (SocialPostModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Save a post entity.
     */
    public function save(SocialPostEntity $post): SocialPostEntity
    {
        $data = [
            'organization_id' => $post->getOrganizationId(),
            'social_account_id' => $post->getSocialAccountId(),
            'content' => $post->getContent(),
            'media_url' => $post->getMediaUrl(),
            'platforms' => $post->getPlatforms(),
            'status' => $post->getStatus(),
            'analytics' => $post->getAnalytics(),
            'scheduled_at' => $post->getScheduledAt()?->format('Y-m-d H:i:s'),
            'published_at' => $post->getPublishedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($post->getId() !== null) {
            SocialPostModel::where('id', $post->getId())->update($data);
            $model = SocialPostModel::find($post->getId());
        } else {
            $model = SocialPostModel::create($data);
        }

        if ($model === null) {
            throw new \RuntimeException('Failed to save social post.');
        }

        $post->setId($model->id);

        return $post;
    }

    /**
     * Delete a post.
     */
    public function delete(int $id): bool
    {
        return (bool) SocialPostModel::where('id', $id)->delete();
    }

    /**
     * Convert an Eloquent model to a domain entity.
     */
    private function toEntity(SocialPostModel $model): SocialPostEntity
    {
        $entity = new SocialPostEntity(
            organizationId: $model->organization_id,
            socialAccountId: $model->social_account_id,
            content: $model->content,
            platforms: $model->platforms ?? [],
            mediaUrl: $model->media_url,
            scheduledAt: $model->scheduled_at !== null ? new DateTimeImmutable($model->scheduled_at) : null,
            analytics: $model->analytics ?? [],
        );

        $entity->setId($model->id);

        $reflection = new \ReflectionClass($entity);

        if ($model->published_at !== null) {
            $prop = $reflection->getProperty('publishedAt');
            $prop->setAccessible(true);
            $prop->setValue($entity, new DateTimeImmutable($model->published_at));
        }

        $statusProp = $reflection->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($entity, $model->status);

        return $entity;
    }
}
