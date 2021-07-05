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
 * RendererInterface
 */
interface RendererInterface
{
    /**
     * Renders a view.
     *
     * @param string $view The view name.
     * @param array $data The view data.
     *
     * @throws ViewNotFoundException
     *
     * @return string The view rendered.
     */
    public function render(string $view, array $data = []): string;

    /**
     * If the view exists.
     *
     * @param string $view The view name.
     * @return bool True if the view exists, otherwise false.
     */        
    public function exists(string $view): bool;
}