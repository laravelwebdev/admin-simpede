<?php

namespace Laravel\Nova\Filters;

class FilterEncoder
{
    /**
     * @var array
     */
    public $filters;

    /**
     * Create a new filter encoder instance.
     *
     * @param  array  $filters
     */
    public function __construct($filters = [])
    {
        $this->filters = $filters;
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
