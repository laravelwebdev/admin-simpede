<?php

namespace Laravel\Nova\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class ResourceMissingException extends Exception
{
    public function __construct(Model $model)
    {
        parent::__construct(
            __('Unable to find Resource for model [:model].', ['model' => get_class($model)])
        );
    }

    /**
     * Create a new exception instance.
     *
     * @param  string  $resource
     * @return static
     */
    public static function forRepeater($resource)
    {
        return new static(__(
            "Unable to find Resource for the given resource name [:resource],
            ['resource' => $resource]
        "));
    }
}
