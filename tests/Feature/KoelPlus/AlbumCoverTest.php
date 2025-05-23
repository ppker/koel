<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Models\Song;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumCoverTest extends PlusTestCase
{
    private MediaMetadataService|MockInterface $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = $this->mock(MediaMetadataService::class);
    }

    #[Test]
    public function albumOwnerCanUploadCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();
        $album->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    #[Test]
    public function albumCoOwnerCanUploadCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();
        $album->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());
        $album->songs()->save(Song::factory()->create());

        self::assertTrue($user->isCoOwnerOfAlbum($album));

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    #[Test]
    public function nonOwnerOrCoOwnerCannotUploadCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();

        self::assertFalse($user->isCoOwnerOfAlbum($album));

        $this->mediaMetadataService->shouldNotReceive('writeAlbumCover');

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }

    #[Test]
    public function adminCanAlwaysUploadCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();
        $album->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_admin())
            ->assertOk();
    }
}
