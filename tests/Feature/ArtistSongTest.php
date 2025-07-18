<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistSongTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $artist = Artist::factory()->create();

        Song::factory(5)->for($artist)->create();

        $this->getAs("api/artists/{$artist->public_id}/songs")
            ->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }
}
