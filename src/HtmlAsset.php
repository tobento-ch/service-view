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

use Stringable;
use Tobento\Service\Support\Htmlable;

/**
 * An asset rendering html instead of a file, e.g. JSON-LD.
 */
class HtmlAsset extends Asset
{
    /**
     * Create a new HtmlAsset.
     *
     * @param Htmlable|Stringable|string $html The html, rendered only when rendering the asset.
     * @param null|string $name The name, used as key. An asset with the same name is replaced.
     *                          If null, a unique name is used, so the asset is always added.
     * @param int $order The priority
     * @param string $group The group
     */
    public function __construct(
        protected Htmlable|Stringable|string $html,
        null|string $name = null,
        int $order = 0,
        string $group = 'default',
    ) {
        parent::__construct(file: $name ?? 'html:'.spl_object_id($this), order: $order, group: $group);
    }
    
    /**
     * Get the evaluated contents of the asset
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->html instanceof Htmlable) {
            return $this->html->toHtml();
        }
        
        return (string) $this->html;
    }
}