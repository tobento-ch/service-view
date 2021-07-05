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
 * ChainRenderer
 */
class ChainRenderer implements RendererInterface
{
    /**
     * @var array<int, RendererInterface>
     */    
    protected array $renderers = [];    

    /**
     * Create a new PhpRenderer.
     *
     * @param RendererInterface $renderers
     */    
    public function __construct(
        RendererInterface ...$renderers
    ) {
        $this->renderers = $renderers;
    }
     
    /**
     * Renders a view.
     *
     * @param string $view The view name.
     * @param array $data The view data.
     * @return string The view rendered.
     */
    public function render(string $view, array $data = []): string
    {        
        foreach($this->renderers as $renderer)
        {
            try {
                return $renderer->render($view, $data);
            } catch (ViewNotFoundException $e) {
                // ignore
            }
        }

        throw new ViewNotFoundException($view, $data);
    }

    /**
     * If the view exists.
     *
     * @param string $view The view name.
     * @return bool True if the view exists, otherwise false.
     */        
    public function exists(string $view): bool
    {
        foreach($this->renderers as $renderer)
        {
            if ($renderer->exists($view) === true)
            {
                return true;
            }
        }
        
        return false;
    }  
}