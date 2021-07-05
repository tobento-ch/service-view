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
 * DirInterface
 */
interface DirInterface
{
    /**
     * Get the dir. Must end with a slash.
     *
     * @return string
     */
    public function dir(): string;

    /**
     * Get the priority.
     *
     * @return int
     */
    public function priority(): int;

    /**
     * Get the group.
     *
     * @return string
     */
    public function group(): string;
}