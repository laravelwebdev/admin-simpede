<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class ActionDropdownComponent extends Component
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return 'div[data-menu-open="true"]';
    }

    /**
     * Run the action with the given URI key.
     */
    public function runWithConfirmation(Browser $browser, string $uriKey): void
    {
        $browser->click("button[data-action-id='{$uriKey}']")
            ->elsewhereWhenAvailable(new Modals\ConfirmActionModalComponent, function ($browser) {
                $browser->confirm();
            });
    }

    /**
     * Run the action with the given URI key.
     */
    public function runWithoutConfirmation(Browser $browser, string $uriKey): void
    {
        $browser->click("button[data-action-id='{$uriKey}']")
            ->elsewhere('', function ($browser) {
                $browser->assertDontSee('@cancel-action-button');
            });
    }

    /**
     * Open the action modal but cancel the action.
     */
    public function select(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $browser->click("button[data-action-id='{$uriKey}']")
            ->elsewhereWhenAvailable(new Modals\ConfirmActionModalComponent, function ($browser) use ($fieldCallback) {
                $fieldCallback($browser);
            });
    }

    /**
     * Open the action modal but cancel the action.
     */
    public function cancel(Browser $browser, string $uriKey): void
    {
        $browser->click("button[data-action-id='{$uriKey}']")
            ->elsewhereWhenAvailable(new Modals\ConfirmActionModalComponent, function ($browser) {
                $browser->cancel();
            });
    }
}
