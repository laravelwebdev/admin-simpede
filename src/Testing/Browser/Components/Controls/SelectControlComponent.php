<?php

namespace Laravel\Nova\Testing\Browser\Components\Controls;

use Laravel\Nova\Testing\Browser\Components\Component;

class SelectControlComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $attribute
    ) {
        //
    }

    /**
     * Get the root selector associated with this component.
     */
    public function selector(): string
    {
        return "select[dusk='{$this->attribute}']";
    }
}
