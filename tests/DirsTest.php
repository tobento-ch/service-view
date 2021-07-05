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
use Tobento\Service\View\Dirs;
use Tobento\Service\View\DirsInterface;
use Tobento\Service\View\Dir;

/**
 * DirsTest tests
 */
class DirsTest extends TestCase
{
    public function testCreateDirs()
    {
        $dirs = new Dirs();
        
        $this->assertInstanceOf(DirsInterface::class, $dirs);
    }

    public function testAddMethod()
    {
        $dirs = new Dirs();
        
        $dir = new Dir('home/private/views/');
        
        $dirs->add($dir);
        
        $this->assertSame($dir, $dirs->all()[0]);
    }
    
    public function testDirMethod()
    {
        $dirs = new Dirs();
        
        $dirs->dir('home/private/views/', 10, 'backend');
        
        $this->assertSame('home/private/views/', $dirs->all()[0]->dir());
        $this->assertSame(10, $dirs->all()[0]->priority());
        $this->assertSame('backend', $dirs->all()[0]->group());
    }    

    public function testAllMethod()
    {
        $dirs = new Dirs();
        $dirs->dir('home/private/views/front/');
        $dirs->dir('home/private/views/back/');
        
        $this->assertSame(2, count($dirs->all()));
    }
    
    public function testGroupMethod()
    {
        $dirs = new Dirs();
        $dirs->dir('home/private/views/front/', 0, 'front');
        $dirs->dir('home/private/views/back/', 0, 'back');
        
        $this->assertSame(0, count($dirs->group()->all()));
        $this->assertSame(1, count($dirs->group('front')->all()));
        $this->assertSame(1, count($dirs->group('back')->all()));
        $this->assertSame(0, count($dirs->group('foo')->all()));
    }     
    
    public function testDefaultGroupMethod()
    {
        $dirs = new Dirs();
        $dirs->dir('home/private/views/back/', 0, 'back');
        
        $this->assertSame(0, count($dirs->group()->all()));
        
        $dirs->defaultGroup('back');
            
        $this->assertSame(1, count($dirs->group()->all()));
    }  
}