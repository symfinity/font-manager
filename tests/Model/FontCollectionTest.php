<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Model;

use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class FontCollectionTest extends TestCase
{
    public function testAdd(): void
    {
        $collection = new FontCollection();
        $font = new Font('Ubuntu', [400], ['normal']);

        $collection->add($font);

        $this->assertCount(1, $collection->all());
    }

    public function testAll(): void
    {
        $font1 = new Font('Ubuntu', [400], ['normal']);
        $font2 = new Font('Roboto', [400], ['normal']);

        $collection = new FontCollection([$font1, $font2]);

        $this->assertCount(2, $collection->all());
    }

    public function testHasSemantic(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);

        $this->assertTrue($collection->hasSemantic('sans'));
        $this->assertFalse($collection->hasSemantic('mono'));
    }

    public function testGetSemantic(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);

        $result = $collection->getSemantic('sans');

        $this->assertNotNull($result);
        $this->assertSame('Ubuntu', $result->getName());
    }

    public function testGetSemanticNotFound(): void
    {
        $collection = new FontCollection();

        $this->assertNull($collection->getSemantic('sans'));
    }

    public function testGetUniqueWeights(): void
    {
        $font1 = new Font('Ubuntu', [300, 400], ['normal']);
        $font2 = new Font('Roboto', [400, 700], ['normal']);

        $collection = new FontCollection([$font1, $font2]);

        $weights = $collection->getUniqueWeights();

        $this->assertSame([300, 400, 700], $weights);
    }

    public function testGetUniqueStyles(): void
    {
        $font1 = new Font('Ubuntu', [400], ['normal']);
        $font2 = new Font('Roboto', [400], ['normal', 'italic']);

        $collection = new FontCollection([$font1, $font2]);

        $styles = $collection->getUniqueStyles();

        $this->assertContains('normal', $styles);
        $this->assertContains('italic', $styles);
    }

    public function testIsEmpty(): void
    {
        $emptyCollection = new FontCollection();
        $filledCollection = new FontCollection([new Font('Ubuntu', [400], ['normal'])]);

        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($filledCollection->isEmpty());
    }

    public function testCount(): void
    {
        $collection = new FontCollection([
            new Font('Ubuntu', [400], ['normal']),
            new Font('Roboto', [400], ['normal']),
        ]);

        $this->assertSame(2, $collection->count());
    }
}
