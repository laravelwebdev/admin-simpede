<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class ComboBoxInputComponent extends SearchInputComponent
{
    /**
     * Search for the given value for a searchable field attribute.
     */
    #[\Override]
    public function searchInput(Browser $browser, string $search, int $pause = 500): void
    {
        $this->showSearchDropdown($browser);

        $browser->type('input[type="search"]', $search);
    }
}
