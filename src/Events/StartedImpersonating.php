<?php

namespace Laravel\Nova\Events;

class StartedImpersonating
{
    /**
     * The impersonator user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $impersonator;

    /**
     * The impersonated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $impersonated;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $impersonated
     */
    public function __construct($impersonator, $impersonated)
    {
        $this->impersonator = $impersonator;
        $this->impersonated = $impersonated;
    }
}
