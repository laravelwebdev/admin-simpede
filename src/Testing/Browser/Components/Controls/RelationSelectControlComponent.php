<?php

namespace Laravel\Nova\Testing\Browser\Components\Controls;

class RelationSelectControlComponent extends SelectControlComponent
{
    /**
     * Get the root selector associated with this component.
     */
    #[\Override]
    public function selector(): string
    {
        return "@{$this->attribute}-select";
    }
}
