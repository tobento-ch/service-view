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
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\RendererInterface;
use Tobento\Service\View\Dirs;
use Tobento\Service\View\ViewNotFoundException;

/**
 * PhpRendererTest tests
 */
class PhpRendererTest extends TestCase
{    
    public function testCreatePhpRenderer()
    {
        $dirs = new Dirs();
        
        $renderer = new PhpRenderer($dirs);
        
        $this->assertInstanceOf(RendererInterface::class, $renderer);
    }
    
    public function testRenderMethodThrowsViewNotFoundException()
    {
        $this->expectException(ViewNotFoundException::class);
        
        $dirs = new Dirs();
        
        $renderer = new PhpRenderer($dirs);
        
        $renderer->render('about');
    }
    
    public function testRenderMethod()
    {        
        $dirs = new Dirs();
        $dirs->dir(__DIR__.'/php-renderer/front/');
        
        $renderer = new PhpRenderer($dirs);
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About</title></head><body>About</body></html>',
            $renderer->render('about', ['title' => 'About'])
        );
    }

    public function testRenderMethodWithSubdirs()
    {        
        $dirs = new Dirs();
        $dirs->dir(__DIR__.'/php-renderer/front/');
        
        $renderer = new PhpRenderer($dirs);
        
        $this->assertSame(
            '<footer>Footer</footer>',
            $renderer->render('inc/footer')
        );
    }
    
    public function testRenderMethodMultipleDirsTakesHigherPriorityDirFirst()
    {        
        $dirs = new Dirs();
        $dirs->dir(__DIR__.'/php-renderer/front/');
        $dirs->dir(__DIR__.'/php-renderer/back/', 10);
        
        $renderer = new PhpRenderer($dirs);
        
        $this->assertSame(
            '<footer>Footer Back</footer>',
            $renderer->render('inc/footer')
        );
    }    
    
    public function testExistsMethod()
    {        
        $dirs = new Dirs();
        $dirs->dir(__DIR__.'/php-renderer/front/');
        
        $renderer = new PhpRenderer($dirs);
        
        $this->assertTrue($renderer->exists('about'));
        $this->assertTrue($renderer->exists('inc/footer'));
        $this->assertFalse($renderer->exists('inc/foo'));
        $this->assertFalse($renderer->exists(''));
        $this->assertFalse($renderer->exists('/'));
    }    
}