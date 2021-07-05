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
 * Dirs
 */
class Dirs implements DirsInterface
{
    /**
     * Create a new Dir.
     *
     * @param array $dirs The directories [DirInterface, ...]
     * @param string $defaultGroup The default group
     */    
    public function __construct(
        protected array $dirs = [],
        protected string $defaultGroup = 'default'
    ) {}

    /**
     * Set the default group.
     *
     * @param string The default group name.
     * @return static
     */
    public function defaultGroup(string $group): static
    {
        $this->defaultGroup = $group;
        return $this;
    }
    
    /**
     * Adds a directory.
     *
     * @param DirInterface $dir
     * @return static $this
     */
    public function add(DirInterface $dir): static
    {        
        $this->dirs[] = $dir;
        return $this;
    }
        
    /**
     * Adds a directory.
     *
     * @param string $dir The directory to the views. Must end with a slash /.
     * @param int $priority The priority. Highest first.
     * @param string $group A dir group name.
     * @return static $this
     */
    public function dir(string $dir, int $priority = 0, string $group = 'default'): static
    {        
        $this->add(new Dir($dir, $priority, $group));
        return $this;
    }

    /**
     * Filters the dirs by group.
     *
     * @param string $group The group name or null for the default group.
     * @return static
     */
    public function group(?string $group = null): static
    {
        $group = $group ?: $this->defaultGroup;
        
        $new = clone $this;
        $new->dirs = array_filter(
            $new->dirs,
            fn (DirInterface $a): bool => $a->group() === $group
        );
        
        return $new;
    }
    
    /**
     * Get all dirs.
     *
     * @return array
     */
    public function all(): array
    {
        uasort(
            $this->dirs,
            fn (DirInterface $a, DirInterface $b): int => $b->priority() <=> $a->priority()
        );
        
        return $this->dirs;
    }    
}