<?php

namespace Laravel\Nova;

use Closure;

trait WithBadge
{
    /**
     * The badge content for the menu item.
     *
     * @var (\Closure():(\Laravel\Nova\Badge|string))|(callable():(\Laravel\Nova\Badge|string))|\Laravel\Nova\Badge|string|null
     */
    public $badgeCallback;

    /**
     * The condition for showing the badge inside the menu item.
     *
     * @var (\Closure():bool)|bool
     */
    public $badgeCondition = true;

    /**
     * The type of badge that should represent the item.
     *
     * @var string
     */
    public $badgeType = 'info';

    /**
     * Set the content to be used for the item's badge.
     *
     * @param  \Laravel\Nova\Badge|(callable():(\Laravel\Nova\Badge|string))|string  $badgeCallback
     * @return $this
     */
    public function withBadge(Badge|callable|string $badgeCallback, string $type = 'info')
    {
        $this->badgeType = $type;

        if (Util::isSafeCallable($badgeCallback) || $badgeCallback instanceof Badge) {
            $this->badgeCallback = $badgeCallback;
        }

        if (is_string($badgeCallback)) {
            $this->badgeCallback = function () use ($badgeCallback, $type) {
                return Badge::make($badgeCallback, $type);
            };
        }

        return $this;
    }

    /**
     * Set the content to be used for the item's badge if the condition matches.
     *
     * @param  \Laravel\Nova\Badge|(callable():(\Laravel\Nova\Badge|string))|string  $badgeCallback
     * @param  (\Closure():(bool))|bool  $condition
     * @return $this
     */
    public function withBadgeIf(Badge|callable|string $badgeCallback, string $type, Closure|bool $condition)
    {
        $this->badgeCondition = $condition;

        $this->withBadge($badgeCallback, $type);

        return $this;
    }

    /**
     * Resolve the badge for the item.
     */
    public function resolveBadge(): ?Badge
    {
        if (value($this->badgeCondition)) {
            if (is_callable($this->badgeCallback)) {
                /** @var \Laravel\Nova\Badge|string|null $result */
                $result = call_user_func($this->badgeCallback);

                if (is_null($result)) {
                    throw new \Exception('A menu item badge must always have a value.');
                }

                if (! $result instanceof Badge) {
                    return Badge::make($result, $this->badgeType);
                }

                return $result;
            }

            return $this->badgeCallback;
        }

        return null;
    }
}
