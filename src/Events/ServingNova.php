<?php

namespace Laravel\Nova\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class ServingNova
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Request $request
    ) {
        //
    }
}
