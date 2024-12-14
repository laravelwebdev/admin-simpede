<?php

namespace Laravel\Nova;

use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesCards
{
    /**
     * Get the cards that are available for the given request.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Metrics\Metric|\Laravel\Nova\Card>
     */
    public function availableCards(NovaRequest $request): Collection
    {
        return $this->resolveCards($request)
            ->reject(fn ($card) => $card->onlyOnDetail)
            ->filter->authorize($request)
            ->values();
    }

    /**
     * Get the cards that are available for the given request.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Metrics\Metric|\Laravel\Nova\Card>
     */
    public function availableCardsForDetail(NovaRequest $request): Collection
    {
        return $this->resolveCards($request)
            ->filter(fn ($card) => $card->onlyOnDetail)
            ->filter->authorize($request)
            ->values();
    }

    /**
     * Get the cards for the given request.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Metrics\Metric|\Laravel\Nova\Card>
     */
    public function resolveCards(NovaRequest $request): Collection
    {
        return collect(array_values($this->filter($this->cards($request))));
    }

    /**
     * Get the cards available on the entity.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }
}
