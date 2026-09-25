<?php

namespace Tests\Unit;

use App\Support\YoutubeUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YoutubeUrlTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function test_it_accepts_youtube_urls(string $url, string $id): void
    {
        $this->assertSame($id, YoutubeUrl::id($url));
        $this->assertSame('https://www.youtube.com/embed/'.$id, YoutubeUrl::embedUrl($url));
    }

    #[DataProvider('invalidUrls')]
    public function test_it_rejects_urls_that_are_not_youtube_videos(string $url): void
    {
        $this->assertNull(YoutubeUrl::id($url));
        $this->assertNull(YoutubeUrl::embedUrl($url));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function validUrls(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch with time' => ['https://youtube.com/watch?v=dQw4w9WgXcQ&t=43', 'dQw4w9WgXcQ'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'vimeo' => ['https://vimeo.com/123456789'],
            'homepage' => ['https://www.youtube.com/'],
            'channel' => ['https://www.youtube.com/channel/UC1234567890'],
            'short id' => ['https://www.youtube.com/watch?v=too-short'],
            'not a url' => ['pas une url'],
        ];
    }
}
