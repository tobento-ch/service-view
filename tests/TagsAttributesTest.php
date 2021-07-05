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
use Tobento\Service\View\TagsAttributes;

/**
 * TagsAttributesTest tests
 */
class TagsAttributesTest extends TestCase
{    
    public function testCreateTagsAttributes()
    {
        $body = (new TagAttributes())->set('data-foo', '1');
        
        $tags = new TagsAttributes([
            'body' => $body,
        ]);
        
        $this->assertSame($body, $tags->get('body'));
    }
    
    public function testHasMethod()
    {        
        $tags = new TagsAttributes([
            'body' => new TagAttributes(),
        ]);
        
        $this->assertTrue($tags->has('body'));
        $this->assertFalse($tags->has('html'));
    }

    public function testGetMethod()
    {
        $body = (new TagAttributes())->set('data-foo', '1');
        
        $tags = new TagsAttributes([
            'body' => $body,
        ]);
        
        $this->assertSame($body, $tags->get('body'));
    }
    
    public function testGetMethodReturnsNewTagAttributesIfNotExists()
    {        
        $tags = new TagsAttributes();
        
        $this->assertInstanceof(TagAttributes::class, $tags->get('html'));
    }

    public function testSetMethod()
    {
        $body = (new TagAttributes())->set('data-foo', '1');
        
        $tags = new TagsAttributes();
        $tags->set('body', $body);
        
        $this->assertSame($body, $tags->get('body'));
    }    
}