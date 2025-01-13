<?php

namespace Laravel\Nova\Events;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class ServingNova
{
    use Dispatchable;

    /**
     * The Application instance.
     */
    public Application $app;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Request $request
    ) {
        /** @phpstan-ignore assign.propertyType */
        $this->app = Container::getInstance();
    }
}
