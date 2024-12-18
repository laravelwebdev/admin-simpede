<?php

namespace Laravel\Nova\Filters;

class FilterEncoder
{
    /**
     * Create a new filter encoder instance.
     */
    public function __construct(public array $filters = [])
    {
        //
    }

    /**
     * Encode the filters into a query string.
     *
     * @return string
     */
    public function encode()
    {
        return base64_encode(json_encode($this->filters));
    }
}
