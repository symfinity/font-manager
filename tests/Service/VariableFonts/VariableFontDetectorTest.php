<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service\VariableFonts;

use Symfinity\FontManager\Service\VariableFonts\VariableFontDetector;
use PHPUnit\Framework\TestCase;

final class VariableFontDetectorTest extends TestCase
{
    private VariableFontDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new VariableFontDetector();
    }

    public function testHasVariableFonts(): void
    {
        $css = '@font-face { font-weight: 100 900; }';

        $result = $this->detector->hasVariableFonts($css);

        $this->assertTrue($result);
    }

    public function testExtractVariableFontAxes(): void
    {
        $css = '@font-face { font-weight: 100 900; }';

        $axes = $this->detector->extractVariableFontAxes($css);

        $this->assertArrayHasKey('wght', $axes);
        $this->assertSame(100.0, $axes['wght']['min']);
        $this->assertSame(900.0, $axes['wght']['max']);
    }

    public function testGenerateWeightRange(): void
    {
        $weights = [100, 400, 700];

        $range = $this->detector->generateWeightRange($weights);

        $this->assertStringStartsWith('wght@', $range);
    }

    public function testIsVariableFontAvailableFromMetadata(): void
    {
        $metadata = [
            'variants' => ['variable', '400', '700'],
        ];

        $result = $this->detector->isVariableFontAvailable($metadata);

        $this->assertTrue($result);
    }
}
