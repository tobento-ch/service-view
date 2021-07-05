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

namespace Tobento\Service\View;

/**
 * TagsAttributes
 */
class TagsAttributes
{        
    /**
     * Create a new TagsAttributes
     *
     * @param array $tags ['tagname' => TagAttributes]
     */
    public function __construct(
        protected array $tags = []
    ) {}

    /**
     * If a tag exists.
     *
     * @param string $tagname The tag name.
     * @return bool True if exist, else false.
     */
    public function has(string $tagname): bool
    {
        return array_key_exists($tagname, $this->tags);
    }

    /**
     * Get the tag attributes.
     *
     * @param string $tagname The tag name.
     * @return TagAttributes
     */
    public function get(string $tagname): TagAttributes
    {
        return $this->tags[$tagname] ??= new TagAttributes();
    }
    
    /**
     * Set a tags attributes
     *
     * @param string $tagname
     * @param TagAttributes $attributes
     * @return static $this
     */
    public function set(string $tagname, TagAttributes $attributes): static
    {        
        $this->tags[$tagname] = $attributes;
        return $this;
    }
}