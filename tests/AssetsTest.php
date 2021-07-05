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
use Tobento\Service\View\Assets;
use Tobento\Service\View\AssetsInterface;
use Tobento\Service\View\Asset;

/**
 * AssetsTest tests
 */
class AssetsTest extends TestCase
{
    public function testCreateAssets()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $this->assertInstanceOf(AssetsInterface::class, $assets);
    }
    
    public function testAddMethod()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $asset = new Asset('inc/styles.css');
        
        $assets->add($asset);
        
        $this->assertSame($asset, $assets->all()['inc/styles.css']);
    }
    
    public function testAssetMethod()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $assets->asset('inc/styles.css');
        
        $this->assertSame(1, count($assets->all()));
    }
    
    public function testRenderMethod()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $this->assertSame(
            '<!-- assets="default" -->',
            $assets->render()
        );
        
        $this->assertSame(
            '<!-- assets="footer" -->',
            $assets->render('footer')
        );
    }
    
    public function testFlushingMethod()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $assets->asset('inc/app.js');
        $assets->asset('inc/app-body.js')->group('body');
            
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>Foo</title><script src="https://www.example.com/src/inc/app.js"></script></head><body><script src="https://www.example.com/src/inc/app-body.js"></script></body></html>',
            $assets->flushing('<!DOCTYPE html><html><head><title>Foo</title>'.$assets->render().'</head><body>'.$assets->render('body').'</body></html>')
        );
    }
    
    public function testFlushingMethodWithNoAssetsReturnsSameContent()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>Foo</title></head><body></body></html>',
            $assets->flushing('<!DOCTYPE html><html><head><title>Foo</title></head><body></body></html>')
        );
    }
    
    public function testAllMethod()
    {
        $assets = new Assets(
            assetDir: 'home/public/src/',
            assetUri: 'https://www.example.com/src/',
        );
        
        $assets->asset('inc/app.js');
        $assets->asset('inc/app1.js');
        $assets->asset('inc/app2.js');
        
        $this->assertSame(3, count($assets->all()));
    }    
}