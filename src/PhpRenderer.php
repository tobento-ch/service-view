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

use Tobento\Service\Dir\DirsInterface;
use Tobento\Service\Dir\DirInterface;
use Tobento\Service\Filesystem\File;
use Throwable;

/**
 * PhpRenderer
 */
class PhpRenderer implements RendererInterface
{    
    /**
     * @var string
     */    
    protected string $renderView = '';

    /**
     * @var array
     */    
    protected array $renderData = [];
    
    /**
     * @var array
     */    
    protected array $verifiedViews = [];

    /**
     * Create a new PhpRenderer.
     *
     * @param DirsInterface $dirs
     */    
    public function __construct(
        protected DirsInterface $dirs
    ) {}
     
    /**
     * Renders a view.
     *
     * @param string $view The view name.
     * @param array $data The view data.
     * @return string The view rendered.
     */
    public function render(string $view, array $data = []): string
    {            
        foreach($this->dirs->all() as $dir)
        {            
            try {
                
                $ensuredView = $this->ensureView($dir, $view);

                if ($ensuredView) {
                    return $this->renderView($ensuredView, $data);            
                }

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
        foreach($this->dirs->all() as $dir)
        {
            if (!is_null($this->ensureView($dir, $view))) {
                return true;
            }
        }
        
        return false;
    }
        
    /**
     * Renders the view.
     *
     * @param string $view The view name.
     * @param array $data The view data.
     *
     * @throws ViewNotFoundException
     *
     * @return string The view rendered.
     */
    protected function renderView(string $view, array $data = []): string
    {
        try {
        
            $this->renderView = $view;
            $this->renderData = $data;
            
            unset($view);
            unset($data);
            
            extract($this->renderData);

            // Start buffer.
            ob_start();    
            
            include $this->renderView;
                        
            $content = ob_get_clean();

            return $content;

        } catch (ViewNotFoundException $e) {
            // ignore subviews not found.
            return '';
        
        } catch (Throwable $e) {

            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            
            throw $e;
        }
    }

    /**
     * Ensure the view.
     *
     * @param DirInterface $dir
     * @param string $view The view name.
     * @return null|string The view or null on failure.
     */
    protected function ensureView(DirInterface $dir, string $view): ?string
    {
        $file = $dir->dir().$view.'.php';
        
        if (in_array($file, $this->verifiedViews)) {
            return $file;
        }
        
        $file = new File($file);
        
        if (!$file->isWithinDir($dir->dir())) {
            return null;
        }
        
        if (!$file->isFile()) {
            return null;
        }
        
        return $this->verifiedViews[] = $file->getFile();
    }
}