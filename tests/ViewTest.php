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
use Tobento\Service\View\View;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\View\Data;
use Tobento\Service\View\DataInterface;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Dir;
use Tobento\Service\View\Dirs;
use Tobento\Service\View\AssetInterface;
use Tobento\Service\View\Assets;
use Tobento\Service\View\AssetsInterface;

/**
 * ViewTest tests
 */
class ViewTest extends TestCase
{   
    protected function createView(): ViewInterface
    {
        return new View(
            new PhpRenderer(
                new Dirs([
                    new Dir(__DIR__.'/view/front/'),
                ])
            ),
            new Data(),
            new Assets(__DIR__.'/view/src/', 'https://www.example.com/src/')
        );
    }

    public function testDataMethodReturnsDataIfNoParametersSet()
    {        
        $this->assertInstanceof(
            DataInterface::class,
            $this->createView()->data()
        );
    }
    
    public function testDataMethodReturnsStaticIfDataIsSet()
    {        
        $this->assertInstanceof(
            ViewInterface::class,
            $this->createView()->data(['title' => 'Lorem'])
        );
    }
    
    public function testDataMethodShouldBeAvailableInViewFile()
    {
        $view = $this->createView()->data(['title' => 'About']);
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About</title></head><body>About</body></html>',
            $view->render('about')
        );
    }
    
    public function testWithMethodShouldBeAvailableInViewFile()
    {
        $view = $this->createView()->with('title', 'About');
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About</title></head><body>About</body></html>',
            $view->render('about')
        );
    }
    
    public function testWithMethodShouldOverwriteExistingData()
    {
        $view = $this->createView();
        
        $view->data(['title' => 'About us']);
        $view->with('title', 'About');
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About</title></head><body>About</body></html>',
            $view->render('about')
        );
    }
    
    public function testWithAndGetMethod()
    {
        $view = $this->createView();
        
        $view->with('title', 'About');
        
        $this->assertSame(
            'About',
            $view->get('title')
        );
    }
    
    public function testDataAndGetMethod()
    {
        $view = $this->createView();
        
        $view->data(['title' => 'About']);
        
        $this->assertSame(
            'About',
            $view->get('title')
        );
    }
    
    public function testGetMethodReturnsDefaultValueIfNotExist()
    {
        $view = $this->createView();
        
        $this->assertSame(
            'About',
            $view->get('title', 'About')
        );
    }
    
    public function testRenderMethodWithDataShouldOverwriteExisting()
    {
        $view = $this->createView();
        
        $view->data(['title' => 'About us']);
        $view->with('title', 'About');
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About new</title></head><body>About</body></html>',
            $view->render('about', ['title' => 'About new'])
        );
    }
    
    public function testRenderMethodReturnsEmptyStringIfViewNotFound()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '',
            $view->render('aboutus')
        );
    }
    
    public function testRenderMethodWithSubdir()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '<footer>Footer</footer>',
            $view->render('inc/footer')
        );
    }
    
    public function testRenderMethodWithRenderInFile()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>Team</title></head><body>Team<footer>Footer</footer></body></html>',
            $view->render('team', ['title' => 'Team'])
        );
    }    
    
    public function testAddMethod()
    {
        $view = $this->createView();
        
        $view->add(key: 'inc.footer', view: 'inc/footer');
        
        $this->assertSame(
            '<footer>Footer</footer>',
            $view->render('inc.footer')
        );
    }
    
    public function testOnMethod()
    {
        $view = $this->createView();
        
        $view->with('title', 'About');
        
        $view->on('about', function(array $data, ViewInterface $view): array {
            $data['title'] = 'About new';
            return $data;
        });
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About new</title></head><body>About</body></html>',
            $view->render('about')
        );
    }
    
    public function testOnMethodWithAddedView()
    {
        $view = $this->createView();
        
        $view->add(key: 'about.keyed', view: 'about');
        
        $view->on('about.keyed', function(array $data, ViewInterface $view): array {
            $data['title'] = 'About new';
            return $data;
        });
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About new</title></head><body>About</body></html>',
            $view->render('about.keyed')
        );
    }
    
    public function testOnMethodWithAddedViewInCallable()
    {
        $view = $this->createView();
                
        $view->on('about.keyed', function(array $data, ViewInterface $view): array {
            
            $view->add(key: 'about.keyed', view: 'about');
            $data['title'] = 'About new';
            return $data;
        });
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About new</title></head><body>About</body></html>',
            $view->render('about.keyed')
        );
    }

    public function testExistsMethod()
    {
        $view = $this->createView();
        
        $this->assertTrue($view->exists('about'));
        $this->assertTrue($view->exists('inc/footer'));
        $this->assertFalse($view->exists('inc/foo'));
    }
    
    public function testExistsMethodWithAddedViews()
    {
        $view = $this->createView();
        
        $view->add(key: 'about.keyed', view: 'about');
        $view->add(key: 'inc.foo', view: 'inc/foo');
        
        $this->assertTrue($view->exists('about.keyed'));
        $this->assertFalse($view->exists('inc.foo'));
    }
    
    public function testOnceMethodShoudRenderOnceInViewFile()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>Once</title></head><body><p>Lorem ipsum</p></body></html>',
            $view->render('once')
        );
    }

    public function testAssetsMethod()
    {
        $view = $this->createView();
        
        $this->assertInstanceof(
            AssetsInterface::class,
            $view->assets()
        );
    }
    
    public function testAssetMethod()
    {
        $view = $this->createView();
        
        $this->assertInstanceof(
            AssetInterface::class,
            $view->asset('app.css')
        );
    }
    
    public function testRenderAssets()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>Assets</title></head><body><script src="https://www.example.com/src/app.js"></script><script src="https://www.example.com/src/service/service.js"></script></body></html>',
            $view->render('assets')
        );
    }
    
    public function testEscMethod()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '&lt;p&gt;Lorem&lt;/p&gt;',
            $view->esc('<p>Lorem</p>', ENT_QUOTES, 'UTF-8', true)
        );
    }    
}