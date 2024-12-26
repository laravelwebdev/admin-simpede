<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class SearchSearchInputComponent extends SearchInputComponent
{
    /**
     * Search for the given value for a searchable field attribute.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @return void
     */
    public function searchInput(Browser $browser, $search, int $pause = 500)
    {
        $this->showSearchDropdown($browser);

        $browser->type('input[type="search"]', $search);
    }
}
