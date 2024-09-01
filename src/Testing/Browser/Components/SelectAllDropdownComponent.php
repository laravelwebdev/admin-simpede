<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class SelectAllDropdownComponent extends Component
{
    /**
     * Get the root selector for the component.
     *
     * @return string
     */
    public function selector()
    {
        return '@select-all-dropdown';
    }

    protected function assertCheckboxStatus(Browser $browser, $status)
    {
        $browser->assertAttribute('@select-all-indicator', 'data-state', $status);
    }

    /**
     * Assert that the checkbox is checked.
     *
     * @return void
     */
    public function assertCheckboxIsChecked(Browser $browser)
    {
        $this->assertCheckboxStatus($browser, 'checked');
    }

    /**
     * Assert that the checkbox is not checked.
     *
     * @return void
     */
    public function assertCheckboxIsNotChecked(Browser $browser)
    {
        $this->assertCheckboxStatus($browser, 'unchecked');
    }

    /**
     * Assert that the checkbox is indeterminate.
     *
     * @return void
     */
    public function assertCheckboxIsIndeterminate(Browser $browser)
    {
        $this->assertCheckboxStatus($browser, 'indeterminate');
    }

    /**
     * Assert select all the the resources on current page is checked.
     *
     * @return void
     */
    public function assertSelectAllOnCurrentPageChecked(Browser $browser)
    {
        $this->assertCheckboxIsIndeterminate($browser);
    }

    /**
     * Assert select all the the resources on current page isn't checked.
     *
     * @return void
     */
    public function assertSelectAllOnCurrentPageNotChecked(Browser $browser)
    {
        $this->assertCheckboxIsNotChecked($browser);
    }

    /**
     * Assert select all the matching resources is checked.
     *
     * @return void
     */
    public function assertSelectAllMatchingChecked(Browser $browser)
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', function (Browser $browser) {
                $browser->assertAttribute('@select-all-matching-button', 'data-state', 'checked');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Assert select all the matching resources isn't checked.
     *
     * @return void
     */
    public function assertSelectAllMatchingNotChecked(Browser $browser)
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', function (Browser $browser) {
                $browser->assertAttribute('@select-all-matching-button', 'data-state', 'unchecked');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Assert on the total selected count text.
     *
     * @param  int  $count
     * @return void
     */
    public function assertSelectedCount(Browser $browser, $count)
    {
        $browser->assertSeeIn('span.font-bold', "{$count} selected");
    }

    /**
     * Assert on the matching total matching count text.
     *
     * @param  int  $count
     * @return void
     */
    public function assertSelectAllMatchingCount(Browser $browser, $count)
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', function (Browser $browser) use ($count) {
                $browser->assertSeeIn('@select-all-matching-count', $count);
            })
            ->closeCurrentDropdown();
    }

    /**
     * Select all the the resources on current page.
     *
     * @return void
     */
    public function selectAllOnCurrentPage(Browser $browser)
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', function ($browser) {
                $browser->click('@select-all-button');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Un-select all the the resources on current page.
     *
     * @return void
     */
    public function unselectAllOnCurrentPage(Browser $browser)
    {
        $browser->waitFor('@deselect-all-button')
            ->click('@deselect-all-button')->pause(250);
    }

    /**
     * Select all the matching resources.
     *
     * @return void
     */
    public function selectAllMatching(Browser $browser)
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', function ($browser) {
                $browser->click('@select-all-matching-button');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Un-select all the matching resources.
     *
     * @return void
     */
    public function unselectAllMatching(Browser $browser)
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger', 2)
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', function ($browser) {
                $browser->click('@select-all-matching-button')->pause(250);
            }, 2);
    }

    /**
     * Assert that the browser page contains the component.
     *
     * @return void
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function assert(Browser $browser)
    {
        tap($this->selector(), function ($selector) use ($browser) {
            $browser->scrollIntoView($selector);
        });
    }
}
