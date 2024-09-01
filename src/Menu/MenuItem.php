<?php

namespace Laravel\Nova\Menu;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Contracts\Filter as FilterContract;
use Laravel\Nova\Filters\FilterEncoder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\Nova;
use Laravel\Nova\URL;
use Laravel\Nova\WithBadge;

/**
 * @method static static make(string $name, string|null $path = null)
 */
class MenuItem implements JsonSerializable
{
    use AuthorizedToSee;
    use Macroable;
    use Makeable;
    use WithBadge;

    /**
     * The menu's component.
     *
     * @var string
     */
    public $component = 'menu-item';

    /**
     * The menu item's name.
     *
     * @var string
     */
    public $name;

    /**
     * The menu's path.
     *
     * @var string|null
     */
    public $path;

    /**
     * The menu's request method (GET, POST, PUT, PATCH, DELETE).
     *
     * @var string
     */
    public $method = 'GET';

    /**
     * The menu's data.
     *
     * @var array<string, string>|null
     */
    public $data = null;

    /**
     * The menu's headers.
     *
     * @var array<string, string>|null
     */
    public $headers = null;

    /**
     * Indicate whether the menu's resolve to an external URL.
     *
     * @var bool
     */
    public $external = false;

    /**
     * The target value for external link.
     *
     * @var string|null
     */
    public $target;

    /**
     * The active menu callback.
     *
     * @var (callable(\Illuminate\Http\Request, \Laravel\Nova\URL):bool)|bool|null
     */
    public $activeMenuCallback;

    /**
     * The filters for the menu item.
     *
     * @var \Illuminate\Support\Collection
     */
    public $filters;

    /**
     * The resource class name.
     *
     * @var class-string<\Laravel\Nova\Resource>|null
     */
    public $resource;

    /**
     * Construct a new Menu Item instance.
     *
     * @param  string  $name
     * @param  string|null  $path
     */
    public function __construct($name, $path = null)
    {
        $this->name = $name;
        $this->path = $path;
        $this->filters = collect();
    }

    /**
     * Create a menu item from a resource class.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return static
     */
    public static function resource($resourceClass)
    {
        return static::make($resourceClass::label())
            ->forResource($resourceClass)
            ->path('/resources/'.$resourceClass::uriKey())
            ->activeWhen(function ($request, $url) {
                return ! $request->routeIs('nova.pages.lens') ? $url->active() : false;
            })
            ->canSee(function ($request) use ($resourceClass) {
                return $resourceClass::availableForNavigation($request) && $resourceClass::authorizedToViewAny($request);
            });
    }

    /**
     * Create a menu item from a lens class.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @param  class-string<\Laravel\Nova\Lenses\Lens>  $lensClass
     * @return static
     */
    public static function lens($resourceClass, $lensClass)
    {
        return with(new $lensClass, function ($lens) use ($resourceClass) {
            return static::make($lens->name())
                ->path('/resources/'.$resourceClass::uriKey().'/lens/'.$lens->uriKey())
                ->canSee(function ($request) use ($lens) {
                    return $lens->authorizedToSee($request);
                });
        });
    }

    /**
     * Create a menu item from a resource with a set of filters.
     *
     * @param  string  $name
     * @param  class-string  $resourceClass
     * @param  \Laravel\Nova\Filters\Filter|null  $filter
     * @param  mixed|null  $value
     * @return static
     */
    public static function filter($name, $resourceClass, $filter = null, $value = null)
    {
        $item = static::make($name)
            ->forResource($resourceClass);

        if ($filter) {
            $item->applies($filter, $value);
        }

        return $item
            ->activeWhen(function ($request, $url) {
                return "/{$request->path()}?{$request->getQueryString()}" === (string) $url;
            })
            ->canSee(function ($request) use ($resourceClass) {
                return $resourceClass::availableForNavigation($request) && $resourceClass::authorizedToViewAny($request);
            });
    }

    /**
     * Apply a filter to the menu item.
     *
     * @param  \Laravel\Nova\Filters\Filter|string  $filter
     * @param  mixed  $value
     * @return $this
     */
    public function applies($filter, $value)
    {
        // If the filter is an actual filter instance, and not a filter generated from
        // a `filterable` field, let's ensure the user is authorized to see it.
        // If not, don't push the filter into the filter's collection.
        if ($filter instanceof FilterContract && ! $filter->authorizedToSee(app(NovaRequest::class))) {
            return $this;
        }

        $this->filters->push([
            'class' => $filter instanceof FilterContract ? get_class($filter) : $filter,
            'value' => $value,
        ]);

        $queryString = $this->queryString();

        $path = '/resources/'.$this->resource::uriKey().'?'.$queryString;

        return $this->path($path);
    }

    /**
     * Set the resource to be used for the menu item.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return $this
     */
    protected function forResource($resourceClass)
    {
        $this->resource = $resourceClass;

        return $this;
    }

    /**
     * Return the query string for a filtered resource menu item.
     *
     * @return string
     */
    protected function queryString()
    {
        return Arr::query([
            $this->resource::uriKey().'_filter' => $this->encodedFilters(),
        ]);
    }

    /**
     * @return string
     */
    public function encodedFilters()
    {
        return (new FilterEncoder($this->filters->all()))->encode();
    }

    /**
     * Set menu's path.
     *
     * @param  string  $href
     * @return $this
     */
    public function path($href)
    {
        $this->path = $href;

        return $this;
    }

    /**
     * Create a menu from dashboard class.
     *
     * @param  class-string<\Laravel\Nova\Dashboard>  $dashboard
     * @return static
     */
    public static function dashboard($dashboard)
    {
        return with(new $dashboard(), function ($dashboard) {
            return static::make(
                $dashboard->label(),
                '/dashboards/'.$dashboard->uriKey()
            )->canSee(function ($request) use ($dashboard) {
                return $dashboard->authorizedToSee($request);
            });
        });
    }

    /**
     * Create menu to an internal Nova path.
     *
     * @param  string  $name
     * @param  string  $path
     * @return static
     */
    public static function link($name, $path)
    {
        return new static($name, $path);
    }

    /**
     * Create menu to an external URL.
     *
     * @param  string  $name
     * @param  string  $path
     * @return static
     */
    public static function externalLink($name, $path)
    {
        return (new static($name, $path))->external();
    }

    /**
     * Marked as external url.
     *
     * @return $this
     */
    public function external()
    {
        $this->external = true;

        return $this;
    }

    /**
     * Set the menu's target to open in a new tab.
     *
     * @return $this
     */
    public function openInNewTab()
    {
        $this->target = '_blank';

        return $this;
    }

    /**
     * Set menu's method, and optionally data or headers.
     *
     * @param  string  $method
     * @param  array<string, mixed>|null  $data
     * @param  array<string, string>|null  $headers
     * @return $this
     */
    public function method($method, $data = null, $headers = null)
    {
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            throw new InvalidArgumentException('Only supports GET, POST, PUT, PATCH or DELETE method');
        }

        $this->method = $method;

        return $this->data($data)->headers($headers);
    }

    /**
     * Set menu's method, and optionally data or headers. This request will be handled via Inertia.visit().
     *
     * @param  string  $method
     * @param  array<string, mixed>|null  $data
     * @param  array<string, string>|null  $headers
     * @return static
     */
    public function inertia($method = 'GET', $data = null, $headers = null)
    {
        if ($method !== 'GET') {
            $headers = Arr::wrap($headers);
        }

        return $this->method($method, $data, $headers);
    }

    /**
     * Set menu's headers.
     *
     * @param  array<string, string>|null  $headers
     * @return $this
     */
    public function headers($headers = null)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set menu's data.
     *
     * @param  array<string, string>|null  $data
     * @return $this
     */
    public function data($data = null)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set menu's name.
     *
     * @param  string  $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Determine the default active URL state.
     *
     * @param  (callable(\Illuminate\Http\Request, \Laravel\Nova\URL):bool)|bool  $activeMenuCallback
     * @return $this
     */
    public function activeWhen($activeMenuCallback)
    {
        $this->activeMenuCallback = $activeMenuCallback;

        return $this;
    }

    /**
     * Determine the default active URL state.
     *
     * @param  (callable(\Illuminate\Http\Request, \Laravel\Nova\URL):bool)|bool  $activeMenuCallback
     * @return $this
     */
    public function activeUnless($activeMenuCallback)
    {
        $this->activeMenuCallback = function ($request, $url) use ($activeMenuCallback) {
            return value($activeMenuCallback, $request, $url) === false;
        };

        return $this;
    }

    /**
     * Prepare the menu for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $url = URL::make($this->path, $this->external);

        $activeMenuCallback = $this->activeMenuCallback ?? function ($request, URL $url) {
            return $url->active();
        };

        return [
            'name' => Nova::__($this->name),
            'component' => $this->component,
            'path' => (string) $url,
            'external' => $this->external,
            'target' => $this->target,
            'method' => $this->method,
            'data' => $this->data,
            'headers' => $this->headers,
            'active' => value($activeMenuCallback, request(), $url),
            'badge' => $this->resolveBadge(),
        ];
    }
}
