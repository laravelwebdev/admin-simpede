<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

class TabIndexComponent extends IndexComponent
{
    /**
     * The tab slug value.
     *
     * @var string
     */
    public $tabSlug;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        string $resourceName,
        ?string $viaRelationship = null,
        ?string $slug = null
    ) {
        parent::__construct($resourceName, $viaRelationship);

        $this->tabSlug = ! is_null($slug) ? $slug : (string) Str::of($this->viaRelationship ?? $this->resourceName)->snake()->slug();
    }

    /**
     * Assert that the browser page contains the component.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    #[\Override]
    public function assert(Browser $browser): void
    {
        $browser->pause(500)
            ->whenAvailable("@{$this->tabSlug}-tab-trigger", function ($browser) {
                $browser->click('');
            });

        parent::assert($browser);
    }
}
