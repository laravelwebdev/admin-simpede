<?php

namespace Laravel\Nova\Filters;

abstract class DateFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'date-filter';

    /**
     * Set the first day of the week.
     *
     * @return $this
     */
    public function firstDayOfWeek(int $day)
    {
        return $this->withMeta([__FUNCTION__ => $day]);
    }
}
