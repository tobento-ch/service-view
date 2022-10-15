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

use Tobento\Service\Tag\AttributesInterface;
use Tobento\Service\Tag\Attributes;

/**
 * TagsAttributes
 */
class TagsAttributes
{        
    /**
     * Create a new TagsAttributes
     *
     * @param array<string, AttributesInterface> $tags
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
     * @return AttributesInterface
     */
    public function get(string $tagname): AttributesInterface
    {
        return $this->tags[$tagname] ??= new Attributes();
    }
    
    /**
     * Set a tags attributes
     *
     * @param string $tagname
     * @param AttributesInterface $attributes
     * @return static $this
     */
    public function set(string $tagname, AttributesInterface $attributes): static
    {        
        $this->tags[$tagname] = $attributes;
        return $this;
    }
}