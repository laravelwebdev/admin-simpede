<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;

class LensCardRequest extends CardRequest
{
    use InteractsWithLenses;

    /**
     * Get all of the possible metrics for the request.
     */
    #[\Override]
    public function availableCards(): Collection
    {
        return $this->lens()->availableCards($this);
    }
}
