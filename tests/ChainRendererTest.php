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
use Tobento\Service\View\ChainRenderer;
use Tobento\Service\Dir\Dir;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View\AssetInterface;
use Tobento\Service\View\Assets;
use Tobento\Service\View\AssetsInterface;
use Tobento\Service\View\Test\Mock\TwigRenderer;

/**
 * ChainRendererTest tests
 */
class ChainRendererTest extends TestCase
{   
    protected function createView(): ViewInterface
    {
        $dirs = new Dirs(
            new Dir(__DIR__.'/view/front/'),
        );
        
        $phpRenderer = new PhpRenderer($dirs);
        
        $twigRenderer = new TwigRenderer(null, $dirs);
        
        $renderer = new ChainRenderer($twigRenderer, $phpRenderer);
        
        return new View(
            $renderer,
            new Data(),
            new Assets(__DIR__.'/view/src/', 'https://www.example.com/src/')
        );
    }

    public function testRenderFirstRendererFoundView()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About us</title><link href="https://www.example.com/src/app.css" rel="stylesheet" type="text/css"><script src="https://www.example.com/src/js/app.js" async></script></head><body><h1>Twig: About us</h1><p></p><footer>Footer</footer></body></html>',
            $view->render('about', ['title' => 'About us'])
        );
    }
    
    public function testRenderFirstRendererNotFoundViewUsesSecondRenderer()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>Assets</title></head><body><script src="https://www.example.com/src/app.js"></script><script src="https://www.example.com/src/service/service.js"></script></body></html>',
            $view->render('assets')
        );
    }
    
    public function testRenderViewNotFoundReturnsEmptyString()
    {
        $view = $this->createView();
        
        $this->assertSame(
            '',
            $view->render('unknown')
        );
    }     
}