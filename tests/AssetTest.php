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
use Tobento\Service\View\Asset;
use Tobento\Service\View\AssetInterface;
use SplFileInfo;

/**
 * AssetTest tests
 */
class AssetTest extends TestCase
{
    public function testCreateAsset()
    {
        $asset = new Asset(
            file: 'inc/styles.css',
            dir: '',
            uri: '',
            attributes: [
                'async'
            ],
            order: 7,
            group: 'footer',
        );
        
        $this->assertInstanceOf(AssetInterface::class, $asset);
    }
    
    public function testGetFileMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame('inc/styles.css', $asset->getFile());
    }
    
    public function testGetFileInfoMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertInstanceOf(SplFileInfo::class, $asset->getFileInfo());
    }
    
    public function testGetDirMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame('', $asset->getDir());
        
        $asset = new Asset('inc/styles.css', dir: 'foo/bar');
        
        $this->assertSame('foo/bar', $asset->getDir());
        
        $asset = new Asset('inc/styles.css');
        $asset->dir('foo/bar');
        
        $this->assertSame('foo/bar', $asset->getDir());
    }
    
    public function testGetUriMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame('', $asset->getUri());
        
        $asset = new Asset('inc/styles.css', uri: 'https://www.example.com/src/');
        
        $this->assertSame('https://www.example.com/src/', $asset->getUri());
        
        $asset = new Asset('inc/styles.css');
        $asset->uri('https://www.example.com/src/');
        
        $this->assertSame('https://www.example.com/src/', $asset->getUri());
    }
    
    public function testGetAttributesMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame([], $asset->getAttributes());
        
        $asset = new Asset('inc/styles.css', attributes: [
            'async'
        ]);
        
        $this->assertSame(['async'], $asset->getAttributes());
    }

    public function testAttrMethod()
    {
        $asset = new Asset('inc/styles.css');
        $asset->attr('async');        
        $this->assertSame(['async' => null], $asset->getAttributes());
        
        $asset = new Asset('inc/styles.css');
        $asset->attr('data-foo', 'foo');
        $asset->attr('data-bar', 'bar');
        $this->assertSame(
            ['data-foo' => 'foo', 'data-bar' => 'bar'],
            $asset->getAttributes()
        );
    }
    
    public function testGetGroupMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame('default', $asset->getGroup());
        
        $asset = new Asset('inc/styles.css', group: 'footer');
        
        $this->assertSame('footer', $asset->getGroup());
        
        $asset = new Asset('inc/styles.css');
        $asset->group('footer');
        
        $this->assertSame('footer', $asset->getGroup());
    }
    
    public function testGetOrderMethod()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame(0, $asset->getOrder());
        
        $asset = new Asset('inc/styles.css', order: 50);
        
        $this->assertSame(50, $asset->getOrder());
        
        $asset = new Asset('inc/styles.css');
        $asset->order(50);
        
        $this->assertSame(50, $asset->getOrder());
    }
    
    public function testRenderMethodCssFile()
    {
        $asset = new Asset('inc/styles.css');
        
        $this->assertSame(
            '<link href="inc/styles.css" rel="stylesheet" type="text/css">',
            $asset->render()
        );
    }
    
    public function testRenderMethodJsFile()
    {
        $asset = new Asset('inc/app.js');
        
        $this->assertSame(
            '<script src="inc/app.js"></script>',
            $asset->render()
        );
    }

    public function testRenderMethodWithUri()
    {
        $asset = new Asset('inc/app.js');
        
        $asset->uri('https://www.example.com/src/');
        
        $this->assertSame(
            '<script src="https://www.example.com/src/inc/app.js"></script>',
            $asset->render()
        );
    }
    
    public function testRenderMethodWithAttributes()
    {
        $asset = new Asset('inc/app.js');
        
        $asset->attr('data-foo', 'foo');
        $asset->attr('async');
        
        $this->assertSame(
            '<script src="inc/app.js" data-foo="foo" async></script>',
            $asset->render()
        );
    }
    
    public function testRenderMethodWithMulipleAttrOfSameKindShouldOverwrite()
    {
        $asset = new Asset('inc/app.js');
        
        $asset->attr('data-foo', 'foo');
        $asset->attr('data-foo', 'bar');
        
        $this->assertSame(
            '<script src="inc/app.js" data-foo="bar"></script>',
            $asset->render()
        );
    }
    
    public function testRenderMethodWithAttrArrayValueShouldConvertToJson()
    {
        $asset = new Asset('inc/app.js');
        
        $asset->attr('data-foo', ['bar' => 'bar']);
        
        $this->assertSame(
            '<script src="inc/app.js" data-foo=\'{&quot;bar&quot;:&quot;bar&quot;}\'></script>',
            $asset->render()
        );
    }
}