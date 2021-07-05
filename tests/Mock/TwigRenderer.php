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

namespace Tobento\Service\View\Test\Mock;

use Tobento\Service\View\RendererInterface;
use Tobento\Service\View\DirsInterface;
use Tobento\Service\View\ViewNotFoundException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Error\LoaderError;

/**
 * TwigRenderer
 */
class TwigRenderer implements RendererInterface
{    
    /**
     * @var null|Environment
     */    
    protected null|Environment $twig = null; 

    /**
     * Create a new TwigRenderer.
     *
     * @param null|Environment $twig
     * @param null|DirsInterface $dirs
     */    
    public function __construct(
        null|Environment $twig = null,
        null|DirsInterface $dirs = null
    )
    {
        if (!is_null($twig)) {
            return;
        }
                
        if ($dirs)
        {
            $loader = new FilesystemLoader();
            
            $dirs = array_reverse($dirs->group()->all());
            
            foreach($dirs as $dir)
            {
                $loader->setPaths($dir->dir());
            }
        } else {
            $loader = new FilesystemLoader(__DIR__.'/views');
        }
        
        $this->twig = new Environment($loader);
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
        try {
            return $this->twig->render($view.'.twig', $data);
        } catch (LoaderError $e) {
            throw new ViewNotFoundException($view, $data);
        }
    }

    /**
     * If the view exists.
     *
     * @param string $view The view name.
     * @return bool True if the view exists, otherwise false.
     */        
    public function exists(string $view): bool
    {        
        return $this->twig->getLoader()->exists($view.'.twig');
    }

    /**
     * Get twig.
     *
     * @return Environment
     */        
    public function twig(): Environment
    {        
        return $this->twig;
    }    
}