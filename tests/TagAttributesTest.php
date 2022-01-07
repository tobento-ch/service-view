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
use Tobento\Service\View\TagAttributes;

/**
 * TagAttributesTest tests
 */
class TagAttributesTest extends TestCase
{    
    public function testEmptyMethod()
    {        
        $this->assertTrue((new TagAttributes())->empty());
        
        $this->assertFalse((new TagAttributes())->set('data-foo', '1')->empty());
        
        $this->assertFalse((new TagAttributes(['data-foo' => '1']))->empty());
    }
    
    public function testHasMethod()
    {        
        $this->assertFalse((new TagAttributes())->has('data-foo'));
        
        $this->assertTrue((new TagAttributes())->set('data-foo', '1')->has('data-foo'));
        
        $this->assertTrue((new TagAttributes(['data-foo' => '1']))->has('data-foo'));
        
        $this->assertTrue((new TagAttributes(['data-foo' => null]))->has('data-foo'));
    }
    
    public function testGetMethod()
    {
        $ta = new TagAttributes(['data-foo' => '1']);
        
        $this->assertSame('1', $ta->get('data-foo'));
        
        $this->assertSame(null, $ta->get('data-bar'));
    }
    
    public function testSetMethod()
    {
        $ta = new TagAttributes();
        
        $ta->set('data-foo', '1');
            
        $this->assertSame('1', $ta->get('data-foo'));
        
        $ta->set('data-foo', '2');
        
        $this->assertSame('2', $ta->get('data-foo'));
    }
    
    public function testAddMethod()
    {
        $ta = new TagAttributes();
        
        $ta->add('class', 'bar');
        
        $this->assertSame('bar', $ta->get('class'));
        
        $ta = new TagAttributes();
        
        $ta->add('class', 'bar');
        $ta->add('class', 'foo');
        
        $this->assertSame(['bar', 'foo'], $ta->get('class'));
    }
    
    public function testMergeMethod()
    {
        $ta = new TagAttributes();
        
        $ta->merge(['class' => 'bar']);
        $ta->merge(['class' => ['foo']]);
        
        $this->assertSame(['bar', 'foo'], $ta->get('class'));
    }
    
    public function testAllMethod()
    {
        $ta = new TagAttributes();
        
        $ta->merge(['class' => 'bar']);
        $ta->set('data-foo', '2');
        
        $this->assertSame(
            [
                'class' => 'bar',
                'data-foo' => '2',
            ],
            $ta->all()
        );
    }
    
    public function testRenderMethod()
    {
        $ta = new TagAttributes();
        
        $ta->set('data-foo', '2');
        
        $this->assertSame(
            'data-foo="2"',
            $ta->render()
        );
    }
    
    public function testRenderMethodWithoutAttributes()
    {
        $ta = new TagAttributes();
        
        $this->assertSame(
            '',
            $ta->render()
        );
    }    
    
    public function testRenderMethodWithClass()
    {
        $ta = new TagAttributes();
        
        $ta->set('class', 'bar');
        $ta->add('class', 'foo');
        
        $this->assertSame(
            'class="bar foo"',
            $ta->render()
        );
    }
    
    public function testRenderMethodWithNullValue()
    {
        $ta = new TagAttributes();
        
        $ta->set('async', null);
        
        $this->assertSame(
            'async',
            $ta->render()
        );
    }
    
    public function testRenderMethodWithEmptyStringValue()
    {
        $ta = new TagAttributes();
        
        $ta->set('data-foo', '');
        
        $this->assertSame(
            'data-foo=""',
            $ta->render()
        );
    }    
    
    public function testRenderMethodWithMulipleAttr()
    {
        $ta = new TagAttributes();
        
        $ta->set('class', 'bar');
        $ta->add('class', 'foo');
        $ta->set('data-foo', '2');
        
        $this->assertSame(
            'class="bar foo" data-foo="2"',
            $ta->render()
        );
    }
    
    public function testRenderMethodWithMulipleAttrOfSameKindShouldConvertToJson()
    {
        $ta = new TagAttributes();
        
        $ta->add('data-foo', 'bar');
        $ta->add('data-foo', 'foo');
        
        $this->assertSame(
            'data-foo=\'[&quot;bar&quot;,&quot;foo&quot;]\'',
            $ta->render()
        );
    }
    
    public function testRenderMethodWithAttrArrayValueShouldConvertToJson()
    {
        $ta = new TagAttributes();
        
        $ta->set('data-foo', ['bar' => 'bar']);
        
        $this->assertSame(
            'data-foo=\'{&quot;bar&quot;:&quot;bar&quot;}\'',
            $ta->render()
        );
    }    
}