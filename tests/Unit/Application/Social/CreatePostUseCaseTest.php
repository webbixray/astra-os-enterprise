<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Social;

use App\Application\Social\DTOs\CreatePostDTO;
use App\Application\Social\DTOs\SocialResponseDTO;
use App\Application\Social\UseCases\CreatePostUseCase;
use App\Domain\Social\Entities\SocialPost;
use App\Domain\Social\Events\SocialPostCreated;
use App\Domain\Social\Repositories\SocialPostRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreatePostUseCase.
 *
 * Tests the social post creation logic with a mocked repository.
 */
final class CreatePostUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Creates a post for a platform and returns a SocialResponseDTO.
     */
    public function test_creates_post_for_platform(): void
    {
        $mockPostRepo = Mockery::mock(SocialPostRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . SocialPostCreated::class);

        $dto = new CreatePostDTO(
            organizationId: 1,
            socialAccountId: 10,
            content: 'Check out our new product launch!',
            platforms: ['twitter', 'linkedin'],
            mediaUrl: 'https://example.com/image.jpg',
        );

        $mockPostRepo
            ->expects()
            ->save(Mockery::type(SocialPost::class))
            ->andReturnUsing(function (SocialPost $post) {
                return $post;
            });

        $eventMock->expects()->dispatch(Mockery::type(SocialPost::class));

        $useCase = new CreatePostUseCase($mockPostRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(SocialResponseDTO::class, $result);
        $this->assertSame('Check out our new product launch!', $result->content);
        $this->assertSame(['twitter', 'linkedin'], $result->platforms);
        $this->assertSame('https://example.com/image.jpg', $result->mediaUrl);
    }

    /**
     * Throws an InvalidArgumentException when the content is empty.
     */
    public function test_validates_content_not_empty(): void
    {
        $mockPostRepo = Mockery::mock(SocialPostRepositoryInterface::class);

        $dto = new CreatePostDTO(
            organizationId: 1,
            socialAccountId: 5,
            content: '',
            platforms: ['twitter'],
        );

        $useCase = new CreatePostUseCase($mockPostRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Post content cannot be empty.');

        $useCase->execute($dto);
    }

    /**
     * Throws an InvalidArgumentException when the content is only whitespace.
     */
    public function test_validates_content_not_only_whitespace(): void
    {
        $mockPostRepo = Mockery::mock(SocialPostRepositoryInterface::class);

        $dto = new CreatePostDTO(
            organizationId: 1,
            socialAccountId: 5,
            content: '   ',
            platforms: ['twitter'],
        );

        $useCase = new CreatePostUseCase($mockPostRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Post content cannot be empty.');

        $useCase->execute($dto);
    }

    /**
     * Throws an InvalidArgumentException when no platforms are specified.
     */
    public function test_validates_at_least_one_platform(): void
    {
        $mockPostRepo = Mockery::mock(SocialPostRepositoryInterface::class);

        $dto = new CreatePostDTO(
            organizationId: 1,
            socialAccountId: 5,
            content: 'Valid content',
            platforms: [],
        );

        $useCase = new CreatePostUseCase($mockPostRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one platform must be specified.');

        $useCase->execute($dto);
    }

    /**
     * Schedules a post for a future date.
     */
    public function test_schedules_post_for_future(): void
    {
        $mockPostRepo = Mockery::mock(SocialPostRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . SocialPostCreated::class);

        $scheduledAt = new DateTimeImmutable('2026-12-25 09:00:00');

        $dto = new CreatePostDTO(
            organizationId: 1,
            socialAccountId: 10,
            content: 'Merry Christmas!',
            platforms: ['twitter', 'facebook'],
            mediaUrl: null,
            scheduledAt: $scheduledAt,
        );

        $mockPostRepo
            ->expects()
            ->save(Mockery::type(SocialPost::class))
            ->andReturnUsing(function (SocialPost $post) {
                return $post;
            });

        $eventMock->expects()->dispatch(Mockery::type(SocialPost::class));

        $useCase = new CreatePostUseCase($mockPostRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(SocialResponseDTO::class, $result);
        $this->assertSame('Merry Christmas!', $result->content);
        $this->assertSame($scheduledAt->format('Y-m-d H:i:s'), $result->scheduledAt->format('Y-m-d H:i:s'));
    }

    /**
     * Creates a post and returns a properly structured SocialResponseDTO.
     */
    public function test_returns_social_response_dto(): void
    {
        $mockPostRepo = Mockery::mock(SocialPostRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . SocialPostCreated::class);

        $dto = new CreatePostDTO(
            organizationId: 1,
            socialAccountId: 20,
            content: 'Exciting news! We are launching next week.',
            platforms: ['linkedin'],
        );

        $mockPostRepo
            ->expects()
            ->save(Mockery::type(SocialPost::class))
            ->andReturnUsing(function (SocialPost $post) {
                return $post;
            });

        $eventMock->expects()->dispatch(Mockery::type(SocialPost::class));

        $useCase = new CreatePostUseCase($mockPostRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(SocialResponseDTO::class, $result);
        $this->assertSame(1, $result->organizationId);
        $this->assertSame(20, $result->socialAccountId);
        $this->assertSame('Exciting news! We are launching next week.', $result->content);
        $this->assertSame(['linkedin'], $result->platforms);
        $this->assertNull($result->publishedAt);
    }
}
