<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\TokenAnalyzer\LaravelProjectDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class LaravelProjectDetectorTest extends TestCase
{
    #[DataProvider('provideApplicationMarkers')]
    public function test_requires_both_application_markers(bool $artisan, bool $bootstrap, bool $expected): void
    {
        $directory = sys_get_temp_dir() . '/ecs-laravel-detector-' . bin2hex(random_bytes(8));

        mkdir($directory);
        mkdir($directory . '/app');
        mkdir($directory . '/bootstrap');

        if ($artisan) {
            file_put_contents($directory . '/artisan', '');
        }

        if ($bootstrap) {
            file_put_contents($directory . '/bootstrap/app.php', '<?php');
        }

        try {
            $this->assertSame($expected, new LaravelProjectDetector()->supports(new SplFileInfo($directory . '/app/Model.php')));
        } finally {

            if ($artisan) {
                unlink($directory . '/artisan');
            }

            if ($bootstrap) {
                unlink($directory . '/bootstrap/app.php');
            }

            rmdir($directory . '/app');
            rmdir($directory . '/bootstrap');
            rmdir($directory);

        }
    }

    public static function provideApplicationMarkers(): array
    {
        return [
            'both markers' => [ true, true, true ],
            'artisan only' => [ true, false, false ],
            'bootstrap only' => [ false, true, false ],
            'no markers' => [ false, false, false ],
        ];
    }

    #[RunInSeparateProcess]
    public function test_recognizes_a_loaded_laravel_application(): void
    {
        eval('namespace Illuminate\\Foundation; class Application {}');

        $this->assertTrue(new LaravelProjectDetector()->supports(new SplFileInfo('/unrelated/File.php')));
    }

    #[RunInSeparateProcess]
    public function test_recognizes_loaded_laravel_helpers(): void
    {
        eval('namespace { function blank(): void {} function filled(): void {} }');

        $this->assertTrue(new LaravelProjectDetector()->supports(new SplFileInfo('/unrelated/File.php')));
    }

    #[RunInSeparateProcess]
    public function test_one_helper_is_insufficient(): void
    {
        eval('namespace { function blank(): void {} }');

        $this->assertFalse(new LaravelProjectDetector()->supports(new SplFileInfo('/unrelated/File.php')));
    }
}
