<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\View\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\View\HtmlAsset;
use Tobento\Service\View\AssetInterface;
use Tobento\Service\Support\Htmlable;
use Tobento\Service\Support\HtmlString;
use Stringable;

class HtmlAssetTest extends TestCase
{
    public function testThatImplementsAssetInterface()
    {
        $this->assertInstanceOf(AssetInterface::class, new HtmlAsset(html: '<p>Lorem</p>'));
    }
    
    public function testNameIsUsedAsFile()
    {
        $asset = new HtmlAsset(html: '<p>Lorem</p>', name: 'lorem');
        
        $this->assertSame('lorem', $asset->getFile());
    }
    
    public function testWithoutNameUsesUniqueName()
    {
        $asset = new HtmlAsset(html: '<p>Lorem</p>');
        $assetOther = new HtmlAsset(html: '<p>Lorem</p>');
        
        $this->assertStringStartsWith('html:', $asset->getFile());
        $this->assertNotSame($asset->getFile(), $assetOther->getFile());
    }
    
    public function testRenderString()
    {
        $asset = new HtmlAsset(html: '<script type="application/ld+json">{"@type":"Article"}</script>');
        
        $this->assertSame('<script type="application/ld+json">{"@type":"Article"}</script>', $asset->render());
        $this->assertSame('<script type="application/ld+json">{"@type":"Article"}</script>', (string) $asset);
    }
    
    public function testRenderHtmlable()
    {
        $asset = new HtmlAsset(html: new HtmlString('<p>Lorem</p>'));
        
        $this->assertSame('<p>Lorem</p>', $asset->render());
    }
    
    public function testRenderStringable()
    {
        $html = new class() implements Stringable {
            public function __toString(): string
            {
                return '<p>Lorem</p>';
            }
        };
        
        $this->assertSame('<p>Lorem</p>', new HtmlAsset(html: $html)->render());
    }
    
    public function testHtmlIsOnlyRenderedOnRender()
    {
        $html = new class() implements Htmlable {
            public array $data = ['@type' => 'Article'];
            
            public function toHtml(): string
            {
                return '<script type="application/ld+json">'.json_encode($this->data).'</script>';
            }
        };
        
        $asset = new HtmlAsset(html: $html);
        
        $html->data['headline'] = 'Lorem';
        
        $this->assertSame(
            '<script type="application/ld+json">{"@type":"Article","headline":"Lorem"}</script>',
            $asset->render()
        );
    }
    
    public function testGroupAndOrder()
    {
        $asset = new HtmlAsset(html: '<p>Lorem</p>');
        
        $this->assertSame('default', $asset->getGroup());
        $this->assertSame(0, $asset->getOrder());
        
        $asset = new HtmlAsset(html: '<p>Lorem</p>', order: 5, group: 'footer');
        
        $this->assertSame('footer', $asset->getGroup());
        $this->assertSame(5, $asset->getOrder());
        
        $asset->group('head')->order(10);
        
        $this->assertSame('head', $asset->getGroup());
        $this->assertSame(10, $asset->getOrder());
    }
}