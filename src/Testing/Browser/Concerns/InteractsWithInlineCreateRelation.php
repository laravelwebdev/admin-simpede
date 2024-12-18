<?php

namespace Laravel\Nova\Testing\Browser\Concerns;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\Modals\CreateRelationModalComponent;

trait InteractsWithInlineCreateRelation
{
    /**
     * Run the inline relation.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function showInlineCreate(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $browser->whenAvailable("@{$uriKey}-inline-create", function ($browser) use ($fieldCallback) {
            $browser->click('')
                ->elsewhereWhenAvailable(new CreateRelationModalComponent, function ($browser) use ($fieldCallback) {
                    $fieldCallback($browser);
                });
        });
    }

    /**
     * Run the inline create relation.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runInlineCreate(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $this->showInlineCreate($browser, $uriKey, function ($browser) use ($fieldCallback) {
            $fieldCallback($browser);

            $browser->click('@create-button')->pause(250);
        });
    }
}
