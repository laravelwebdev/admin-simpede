<?php

namespace Laravel\Nova\Testing\Browser\Concerns;

use Carbon\CarbonInterface;
use Facebook\WebDriver\Exception\WebDriverException;
use Illuminate\Support\Env;
use Laravel\Dusk\Browser;

trait InteractsWithElements
{
    /**
     * Dismiss toasted messages.
     *
     * @return void
     */
    public function dismissToasted(Browser $browser)
    {
        $browser->script('Nova.$toasted.clear()');
    }

    /**
     * Close current dropdown.
     *
     * @return void
     */
    public function closeCurrentDropdown(Browser $browser, bool $throwIfMissing = false)
    {
        try {
            $browser->elseWhereWhenAvailable('@dropdown-teleported', function (Browser $browser) {
                $element = $browser->element('@dropdown-overlay');

                if (! is_null($element) && $element->isDisplayed()) {
                    $browser->click('@dropdown-overlay')->waitUntilMissing('@dropdown-overlay');
                }
            });
        } catch (WebDriverException $e) {
            if ($throwIfMissing === true) {
                throw $e;
            }
        }
    }

    /**
     * Type on "date" input.
     *
     * @param  \Carbon\CarbonInterface|empty-string|null  $carbon
     * @return void
     */
    public function typeOnDate(Browser $browser, string $selector, $carbon)
    {
        if ($carbon instanceof CarbonInterface) {
            $date = $carbon->format(Env::get('DUSK_DATE_FORMAT', 'mdY'));

            $this->typeWithTabs($browser, $selector, $date);
        } else {
            $browser->type($selector, '');
        }

        $browser->pause(1000);
    }

    /**
     * Type in a "datetime" filter input.
     *
     * @param  \Carbon\CarbonInterface|empty-string|null  $carbon
     * @return void
     */
    public function typeInDateTimeField(Browser $browser, string $selector, $carbon)
    {
        if ($carbon instanceof CarbonInterface) {
            $this->typeWithTabs($browser, $selector, $carbon->format(Env::get('DUSK_DATETIME_FORMAT', 'mdY-hia')));
            $browser->keys($selector, ['{tab}']);
        } else {
            $browser->type($selector, '');
        }

        $browser->pause(1000);
    }

    /**
     * Type on "datetime-local" input.
     *
     * @param  \Carbon\CarbonInterface|empty-string|null  $carbon
     * @return void
     */
    public function typeOnDateTimeLocal(Browser $browser, string $selector, $carbon)
    {
        if ($carbon instanceof CarbonInterface) {
            $date = $carbon->format(Env::get('DUSK_DATE_FORMAT', 'mdY'));
            $time = $carbon->format(Env::get('DUSK_TIME_FORMAT', 'hisa'));

            $this->typeWithTabs($browser, $selector, $date);
            $browser->keys($selector, ['{tab}']);
            $this->typeWithTabs($browser, $selector, $time);
        } else {
            $browser->type($selector, '');
        }
    }

    /**
     * Type input separated using "tab".
     *
     * @return void
     */
    protected function typeWithTabs(Browser $browser, string $selector, string $date, string $separator = '-')
    {
        $date = explode($separator, $date);

        array_map(function ($group) use ($date, $browser, $selector) {
            if (strtolower($group) === 'am') {
                $browser->type($selector, 'a');
            } elseif (strtolower($group) === 'pm') {
                $browser->type($selector, 'p');
            } else {
                $browser->type($selector, $group);
            }

            // if the item is not the last in the array, let's tab through
            if ($group !== end($date)) {
                $browser->keys($selector, ['{tab}']);
            }
        }, $date);
    }

    /**
     * Assert active modal is present.
     *
     * @return void
     */
    public function assertPresentModal(Browser $browser)
    {
        $browser->assertPresent('.modal[data-modal-open=true]');
    }

    /**
     * Assert active modal is missing.
     *
     * @return void
     */
    public function assertMissingModal(Browser $browser)
    {
        $browser->assertMissing('.modal[data-modal-open=true]');
    }
}
