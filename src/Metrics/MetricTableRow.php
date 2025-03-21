<?php

namespace Laravel\Nova\Metrics;

use Closure;
use JsonSerializable;
use Laravel\Nova\Makeable;
use Laravel\Nova\WithIcon;
use Laravel\SerializableClosure\SerializableClosure;
use Serializable;
use Stringable;

class MetricTableRow implements JsonSerializable, Serializable
{
    use Makeable;
    use WithIcon;

    /**
     * The icon of the metric row.
     *
     * @var string
     */
    public $icon;

    /**
     * The icon class of the metric row.
     *
     * @var string
     */
    public $iconClass;

    /**
     * The title of the metric row.
     *
     * @var \Stringable|string
     */
    public $title;

    /**
     * The subtitle of the metric row.
     *
     * @var \Stringable|string
     */
    public $subtitle;

    /**
     * The action callback used to generate the actions for the metric row.
     *
     * @var (\Closure():(array))|null
     */
    public $actionCallback;

    /**
     * Create a new Metric row.
     */
    public function __construct()
    {
        $this->actionCallback = static fn () => [];
    }

    /**
     * Set the icon for the metric row.
     *
     * @return $this
     */
    public function icon(string $icon)
    {
        $this->withIcon($icon);

        return $this;
    }

    /**
     * Set the icon class for the metric row.
     *
     * @return $this
     */
    public function iconClass(string $class)
    {
        $this->iconClass = $class;

        return $this;
    }

    /**
     * Set the title for the metric row.
     *
     * @return $this
     */
    public function title(Stringable|string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the subtitle for the metric row.
     *
     * @return $this
     */
    public function subtitle(Stringable|string $subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Set the actions used for the metric row.
     *
     * @param  \Closure():array  $actionCallback
     * @return $this
     */
    public function actions(Closure $actionCallback)
    {
        $this->actionCallback = $actionCallback;

        return $this;
    }

    /**
     * Prepare the metric row for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'icon' => $this->icon,
            'iconClass' => $this->iconClass,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'actions' => \call_user_func($this->actionCallback),
        ];
    }

    /**
     * Serialize current object.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'icon' => $this->icon,
            'iconClass' => $this->iconClass,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'actions' => new SerializableClosure($this->actionCallback),
        ]);
    }

    /**
     * Unserialize current object.
     *
     * @param  string  $data
     * @return void
     */
    public function unserialize($data)
    {
        $payload = unserialize($data);

        $this->icon = $payload['icon'];
        $this->iconClass = $payload['iconClass'];
        $this->title = $payload['title'];
        $this->subtitle = $payload['subtitle'];
        $this->actionCallback = $payload['actions']->getClosure();
    }

    /**
     * Serialize current object.
     *
     * @return array<string, mixed>
     */
    public function __serialize()
    {
        return [
            'icon' => $this->icon,
            'iconClass' => $this->iconClass,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'actions' => new SerializableClosure($this->actionCallback),
        ];
    }

    /**
     * Unserialize current object.
     *
     * @param  array<string, mixed>  $data
     * @return void
     */
    public function __unserialize(array $data)
    {
        $this->icon = $data['icon'];
        $this->iconClass = $data['iconClass'];
        $this->title = $data['title'];
        $this->subtitle = $data['subtitle'];
        $this->actionCallback = $data['actions']->getClosure();
    }
}
