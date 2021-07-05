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

use InvalidArgumentException;

/**
 * ViewNotFoundException
 */
class ViewNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new ViewNotFoundException exception.
     *
     * @param string $view The view name.
     * @param array $data The view data.
     * @param null|string $message The exception message.
     */    
    public function __construct(
        protected string $view,
        protected array $data = [],
        ?string $message = null
    ) {
        parent::__construct($message ?? "View [$view] not found.");
    }

    /**
     * Get the view.
     *
     * @return string
     */
    public function view(): string
    {
        return $this->view;
    }

    /**
     * Get the data
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }    
}