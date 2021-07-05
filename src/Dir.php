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
 * Dir
 */
class Dir implements DirInterface
{
    /**
     * Create a new Dir.
     *
     * @param string $dir The directory
     * @param int $priority The priority
     * @param string $group The group
     */    
    public function __construct(
        protected string $dir,
        protected int $priority = 0,
        protected string $group = 'default'
    ) {
        // Normalize dir.
        $this->dir = rtrim($dir, '/').'/';    
    }
    
    /**
     * Get the dir. Must end with a slash.
     *
     * @return string
     */
    public function dir(): string
    {
        return $this->dir;
    }

    /**
     * Get the priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
    }

    /**
     * Get the group.
     *
     * @return string
     */
    public function group(): string
    {
        return $this->group;
    }            
}