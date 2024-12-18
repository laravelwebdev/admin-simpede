<?php

namespace Laravel\Nova;

use Laravel\Nova\Fields\FieldElement;

class ResourceToolElement extends FieldElement
{
    /**
     * Create a new resource tool.
     *
     * @return void
     */
    public function __construct(?string $component = null)
    {
        parent::__construct($component);

        $this->onlyOnDetail();
    }
}
