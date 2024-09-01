<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Support\Arr;
use Laravel\Dusk\Browser;
use Laravel\Dusk\ElementResolver;

class SearchInputComponent extends Component
{
    public $attribute;

    public $mode;

    /**
     * Create a new component instance.
     *
     * @param  string  $attribute
     * @param  string  $mode
     * @return void
     */
    public function __construct(string $attribute, string $mode = 'input')
    {
        $this->attribute = $attribute;
        $this->mode = $mode;
    }

    /**
     * Show the component dropdown.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @return void
     */
    public function showSearchDropdown(Browser $browser)
    {
        $resolver = new ElementResolver($browser->driver, 'body');

        $input = $resolver->find("[dusk='{$this->attribute}-search-{$this->mode}-dropdown']");

        if (is_null($input) || ! $input->isDisplayed()) {
            $browser->click('');
        }
    }

    /**
     * Search for the given value for a searchable field attribute.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @param  int  $pause
     * @return void
     */
    public function searchInput(Browser $browser, $search, int $pause = 500)
    {
        $this->showSearchDropdown($browser);

        $browser->elsewhereWhenAvailable("{$this->selector()}-dropdown", function ($browser) use ($search) {
            $browser->type('input[type="search"]', $search);
        });

        $browser->pause($pause);
    }

    /**
     * Reset the searchable field.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @return void
     */
    public function resetSearchResult(Browser $browser)
    {
        $this->cancelSelectingSearchResult($browser);

        $selector = "{$this->selector()}-clear-button";

        $element = $browser->element($selector);

        if (! is_null($element) && $element->isDisplayed()) {
            $browser->click($selector)->pause(1500);
        }
    }

    /**
     * Search and select the searchable field by result index.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @param  int  $resultIndex
     * @return void
     */
    public function searchAndSelectResult(Browser $browser, $search, $resultIndex)
    {
        $this->searchInput($browser, $search, 1500);

        $this->selectSearchResult($browser, $resultIndex);
    }

    /**
     * Select the searchable field by result index.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  int  $resultIndex
     * @return void
     */
    public function selectSearchResult(Browser $browser, $resultIndex)
    {
        $browser->elseWhereWhenAvailable("{$this->selector()}-dropdown", function ($browser) use ($resultIndex) {
            $browser->whenAvailable("{$this->selector()}-result-{$resultIndex}", function ($browser) {
                $browser->click('')->pause(300);
            });
        });
    }

    /**
     * Select the currently highlighted searchable field.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @return void
     */
    public function cancelSelectingSearchResult(Browser $browser)
    {
        $browser->driver->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);

        $browser->pause(150);
    }

    /**
     * Select the currently highlighted searchable field.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @return void
     */
    public function selectFirstSearchResult(Browser $browser)
    {
        $this->selectSearchResult($browser, 0);
    }

    /**
     * Search and select the currently highlighted searchable relation.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @return void
     */
    public function searchFirstRelation(Browser $browser, $search)
    {
        $this->searchAndSelectFirstResult($browser, $search);
    }

    /**
     * Search and select the currently highlighted searchable field.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @return void
     */
    public function searchAndSelectFirstResult(Browser $browser, $search)
    {
        $this->searchAndSelectResult($browser, $search, 0);
    }

    /**
     * Assert on searchable results.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  callable(\Laravel\Nova\Browser, string):void  $fieldCallback
     * @return void
     */
    public function assertSearchResult(Browser $browser, callable $fieldCallback)
    {
        $this->showSearchDropdown($browser);

        $browser->elsewhereWhenAvailable("{$this->selector()}-dropdown", function ($browser) use ($fieldCallback) {
            $fieldCallback($browser, $this->selector());

            $this->cancelSelectingSearchResult($browser);
        });
    }

    /**
     * Assert on searchable results is locked to single result.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @return void
     */
    public function assertSelectedSearchResult(Browser $browser, $search)
    {
        $browser->assertSeeIn("{$this->selector()}-selected", $search);
    }

    /**
     * Assert on searchable results is locked to single result.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $search
     * @return void
     */
    public function assertSelectedFirstSearchResult(Browser $browser, $search)
    {
        $this->assertSelectedSearchResult($browser, $search);

        $this->assertSearchResult($browser, function ($browser, $attribute) use ($search) {
            $browser->assertSeeIn("{$attribute}-result-0", $search)
                ->assertNotPresent("{$attribute}-result-1")
                ->assertNotPresent("{$attribute}-result-2")
                ->assertNotPresent("{$attribute}-result-3")
                ->assertNotPresent("{$attribute}-result-4");
        });
    }

    /**
     * Assert on searchable results is empty.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @return void
     */
    public function assertEmptySearchResult(Browser $browser)
    {
        $this->assertSearchResult($browser, function ($browser, $attribute) {
            $browser->assertNotPresent("{$attribute}-result-0")
                ->assertNotPresent("{$attribute}-result-1")
                ->assertNotPresent("{$attribute}-result-2")
                ->assertNotPresent("{$attribute}-result-3")
                ->assertNotPresent("{$attribute}-result-4");
        });
    }

    /**
     * Assert on searchable results has the search value.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string|array  $search
     * @return void
     */
    public function assertSearchResultContains(Browser $browser, $search)
    {
        $this->assertSearchResult($browser, function ($browser, $attribute) use ($search) {
            foreach (Arr::wrap($search) as $keyword) {
                $browser->assertSeeIn("{$attribute}-results", $keyword);
            }
        });
    }

    /**
     * Assert on searchable results doesn't has the search value.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string|array  $search
     * @return void
     */
    public function assertSearchResultDoesNotContains(Browser $browser, $search)
    {
        $this->assertSearchResult($browser, function ($browser, $attribute) use ($search) {
            foreach (Arr::wrap($search) as $keyword) {
                $browser->assertDontSeeIn("{$attribute}-results", $keyword);
            }
        });
    }

    /**
     * Assert that the current page contains this component.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->waitFor($this->selector());
    }

    /**
     * Get the root selector associated with this component.
     *
     * @return string
     */
    public function selector()
    {
        return "@{$this->attribute}-search-{$this->mode}";
    }
}
