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

use Tobento\Service\Macro\Macroable;
use InvalidArgumentException;
use BadMethodCallException;

/**
 * View
 */
class View implements ViewInterface
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var DataInterface
     */    
    protected DataInterface $data;
    
    /**
     * @var array
     */    
    protected array $views = [];

    /**
     * @var array The view exists cached.
     */    
    protected array $viewExists = [];    
    
    /**
     * @var array The views callables on render.
     */    
    protected array $on = [];

    /**
     * @var array
     */    
    protected array $rendering = [];
    
    /**
     * @var int The level of active rendering operations.
     */    
    protected int $renderLevel = 0;    

    /**
     * @var array
     */    
    protected array $once = [];
                
    /**
     * Create a new View.
     *
     * @param RendererInterface $renderer
     * @param null|DataInterface $data
     * @param null|AssetsInterface $assets
     */    
    public function __construct(
        protected RendererInterface $renderer,
        ?DataInterface $data = null,
        protected ?AssetsInterface $assets = null
    ) {
        $this->data = $data ?: new Data();
        $this->assets = $assets ?: new Assets('', '');
    }

    /**
     * Add or get data collection
     *
     * @param null|array $data
     * @return static|DataInterface
     */
    public function data(?array $data = null): static|DataInterface
    {
        if (is_null($data)) {
            return $this->data;
        }
        
        $this->data->add($data);
        
        return $this;
    }

    /**
     * Add data to the view.
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function with(string $name, mixed $value): static
    {        
        $this->data->set($name, $value);
        
        return $this;
    }
    
    /**
     * Get data by name.
     *
     * @param string $name The name
     * @param mixed $default A default value
     * @return mixed The value
     */
    public function get(string $name, mixed $default = null): mixed
    {        
        return $this->data->get($name, $default);
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
        // prevent circular dependency error.
        if (isset($this->rendering[$view])) {
            throw new InvalidArgumentException(
                'The ['.$view.'] cannot be called inside of its own view to prevent circular dependency.'
            );
        }
        
        $this->renderLevel++;
        
        $this->rendering[$view] = true;
        
        $data = $this->handleOnCallables($view, $data);
        
        // Add data as to share them.
        $this->data->add($data);
        
        // If it is an added view, get the view.
        $viewName = $this->views[$view] ?? $view;
    
        // Get the data based on the view set on render.
        // So data must be assign to view keys added.
        $data = $this->data->all($view);

        if (isset($data['view'])) {
            throw new InvalidArgumentException('[view] is a reserved data key.');
        }
    
        $data['view'] = $this;        

        try {
            $content = $this->renderer->render($viewName, $data);
        } catch (ViewNotFoundException $e) {
            $content = '';
        }
                    
        unset($this->rendering[$view]);
        
        $this->renderLevel--;
        
        return $this->flushing($content);
    }
        
    /**
     * On render view.
     *
     * @param string $view The view name.
     * @param callable $callable function(array $data, ViewInterface $view) { return []; // the data }
     * @return static $this
     */
    public function on(string $view, callable $callable): static
    {
        $this->on[$view][] = $callable;
        
        return $this;
    }
    
    /**
     * Add a view by key.
     *
     * @param string $key The view key.
     * @param string $view The view name.
     * @return static $this
     */        
    public function add(string $key, string $view): static
    {        
        $this->views[$key] = $view;
        
        return $this;
    }

    /**
     * If the view exists.
     *
     * @param string $view The view name.
     * @return bool True if the view exists, otherwise false.
     */        
    public function exists(string $view): bool
    {        
        $view = $this->views[$view] ?? $view;
        
        // Cacheing for better performance.
        if (array_key_exists($view, $this->viewExists)) {
            return $this->viewExists[$view];
        }
            
        return $this->viewExists[$view] = $this->renderer->exists($view);
    }
    
    /**
     * Render once only.
     *
     * @param string $key Any identifier key.
     * @return bool True if not rendered yet, otherwise false.
     */        
    public function once(string $key): bool
    {
        $once = !isset($this->once[$key]);
        
        $this->once[$key] = true;
        
        return $once;
    }
    
    /**
     * Get the assets.
     *
     * @return AssetsInterface
     */        
    public function assets(): AssetsInterface
    {
        return $this->assets ?: new Assets('', '');
    }

    /**
     * Add and get an asset.
     *
     * @param string $file The file
     * @return AssetInterface
     */        
    public function asset(string $file): AssetInterface
    {        
        return $this->assets()->asset($file);
    }    

    /**
     * Escapes html with htmlspecialchars.
     * 
     * @param mixed $string See php doc http://php.net/manual/en/function.htmlspecialchars.php
     * @param int $flags See php doc.
     * @param string $encoding See php doc
     * @param bool $double_encode See php doc
     * @return string
     */
    public function esc(
        mixed $string,
        int $flags = ENT_QUOTES,
        string $encoding = 'UTF-8',
        bool $double_encode = true
    ): string {
        return htmlspecialchars((string) $string, $flags, $encoding, $double_encode);
    }
    
    /**
     * Add a macro.
     *
     * @param string $name The macro name.
     * @param object|callable $macro
     * @return static
     */
    public function addMacro(string $name, object|callable $macro): static
    {
        $this->macro($name, $macro);
        
        return $this;
    }
    
    /**
     * Dynamically bind parameters to the view.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
    
    /**
     * Flushing.
     *
     * @param string $content The content.
     * @return string The content.
     */
    protected function flushing(string $content): string
    {
        if ($this->renderLevel === 0) {
            $content = $this->assets()->flushing($content);
            $this->once = [];
        }
        
        return $content;
    }
        
    /**
     * Handle the on view callables
     *
     * @param string $view The view name.
     * @param array $data The view data.
     * @return array The view data
     */        
    protected function handleOnCallables(string $view, array $data = []): array
    {
        if (!isset($this->on[$view])) {
            return $data;
        }
        
        foreach($this->on[$view] as $callable)
        {            
            $data = call_user_func_array($callable, [$data, $this]);
        }
        
        return $data;
    }        
}