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
use Tobento\Service\View\Data;
use Tobento\Service\View\DataInterface;

/**
 * DataTest tests
 */
class DataTest extends TestCase
{
    public function testCreateData()
    {
        $data = new Data();
        
        $this->assertInstanceOf(DataInterface::class, $data);
    }
    
    public function testSetMethod()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem');
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all()
        );
    }
    
    public function testSetMethodWithSpecificView()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem', 'inc/footer');
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all('inc/footer')
        );
    }
    
    public function testSetMethodWithMultipleViews()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem', ['inc/footer', 'inc/header']);
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all('inc/footer')
        );
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all('inc/header')
        );
    }

    public function testAddMethod()
    {
        $data = new Data();
        
        $data->add(['title' => 'Lorem']);
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all()
        );
    }
    
    public function testAddMethodWithSpecificView()
    {
        $data = new Data();
        
        $data->add(['title' => 'Lorem'], 'inc/footer');
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all('inc/footer')
        );
    }

    public function testAddMethodWithMultipleViews()
    {
        $data = new Data();
        
        $data->add(['title' => 'Lorem'], ['inc/footer', 'inc/header']);
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all('inc/footer')
        );
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all('inc/header')
        );
    }
    
    public function testGetMethod()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem');
        
        $this->assertSame('Lorem', $data->get('title'));
    }
    
    public function testGetMethodReturnsDefaultIfDataDoesNotExist()
    {
        $data = new Data();
        
        $this->assertSame('default', $data->get('title', 'default'));
    }    
    
    public function testGetMethodCannotGetSpecificViewData()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem', 'inc/header');
        
        $this->assertSame(null, $data->get('title'));
    }
    
    public function testAllMethod()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem');
        
        $this->assertSame(
            [
                'title' => 'Lorem',
            ],
            $data->all()
        );
    }
    
    public function testAllMethodWithSpecificViewMergesSharedView()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem');
        $data->set('locale', 'en', 'footer');
        
        $this->assertSame(
            [
                'title' => 'Lorem',
                'locale' => 'en',
            ],
            $data->all('footer')
        );
    }
    
    public function testRenameMethod()
    {
        $data = new Data();
        
        $data->set('title', 'Lorem');
        
        $allData = $data->rename(['title' => 'titleNew'])->add(['bar' => 'foo'])->all();
        
        $this->assertSame(
            [
                'titleNew' => 'Lorem',
                'bar' => 'foo',
            ],
            $allData
        );
    }    
}