<?php

namespace Laravel\Nova;

trait WithIcon
{
    /**
     * The type of icon that should represent the item.
     *
     * @var string|null
     */
    public $icon = null;

    /**
     * Set the content to be used for the item's icon.
     *
     * @param  string  $icon
     * @return $this
     */
    public function withIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }
}
