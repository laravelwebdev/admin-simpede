<?php

namespace Laravel\Nova;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stringable;

class Util
{
    /**
     * Determine if the given request is intended for Nova.
     */
    public static function isNovaRequest(Request $request): bool
    {
        $domain = config('nova.domain');
        $path = trim(Nova::path(), '/') ?: '/';

        if (! is_null($domain) && $domain !== config('app.url') && $path === '/') {
            if (! Str::startsWith($domain, ['http://', 'https://', '://'])) {
                $domain = $request->getScheme().'://'.$domain;
            }

            if (! in_array($port = $request->getPort(), [443, 80]) && ! Str::endsWith($domain, ":{$port}")) {
                $domain = $domain.':'.$port;
            }

            $uri = parse_url($domain);

            return isset($uri['port'])
                        ? rtrim($request->getHttpHost(), '/') === $uri['host'].':'.$uri['port']
                        : rtrim($request->getHttpHost(), '/') === $uri['host'];
        }

        return $request->is($path) ||
               $request->is(trim($path.'/*', '/')) ||
               $request->is('nova-api/*') ||
               $request->is('nova-vendor/*');
    }

    /**
     * Convert large integer higher than Number.MAX_SAFE_INTEGER to string.
     *
     * https://stackoverflow.com/questions/47188449/json-max-int-number/47188576
     */
    public static function safeInt(mixed $value): mixed
    {
        $jsonMaxInt = 9007199254740991;

        if (is_int($value) && $value >= $jsonMaxInt) {
            return (string) $value;
        } elseif (filter_var($value, FILTER_VALIDATE_INT) && $value < $jsonMaxInt) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Determine if the value is a callable and not a string matching an available function name.
     */
    public static function isSafeCallable(mixed $value): bool
    {
        if ($value instanceof Closure) {
            return true;
        }

        if (! is_callable($value)) {
            return false;
        }

        if (is_array($value)) {
            return count($value) === 2 && ! Arr::isAssoc($value) && method_exists(...$value);
        }

        return ! is_string($value);
    }

    /**
     * Hydrate the value to scalar (array, string, int etc...).
     *
     * @return scalar
     */
    public static function hydrate(mixed $value)
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        } elseif (is_object($value) && $value instanceof Stringable) {
            return (string) $value;
        } elseif (is_object($value) || is_array($value)) {
            return rescue(fn () => json_encode($value), $value);
        }

        return $value;
    }

    /**
     * Resolve given value.
     */
    public static function value(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return value($value);
    }

    /**
     * Get the user guard for Laravel Nova.
     */
    public static function userGuard(): string
    {
        return config('nova.guard') ?? config('auth.defaults.guard');
    }

    /**
     * Get the user model for Laravel Nova.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public static function userModel(): ?string
    {
        return static::userModelFromGuard(static::userGuard());
    }

    /**
     * Get the user model for Laravel Nova.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public static function userModelFromGuard(string $guard): ?string
    {
        $provider = config("auth.guards.{$guard}.provider");

        return config("auth.providers.{$provider}.model");
    }

    /**
     * Get the session auth guard for the model.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Model  $model
     */
    public static function sessionAuthGuardForModel($model): ?string
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        $provider = collect(config('auth.providers'))->reject(function ($provider) use ($model) {
            return ! ($provider['driver'] === 'eloquent' && is_a($model, $provider['model'], true));
        })->keys()->first();

        return collect(config('auth.guards'))->reject(function ($guard) use ($provider) {
            return ! ($guard['driver'] === 'session' && $guard['provider'] === $provider);
        })->keys()->first();
    }

    /**
     * Resolve the model/resoruce for policy.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model
     */
    public static function resolveResourceOrModelForAuthorization(Resource $resource): Model|Resource
    {
        if (property_exists($resource, 'policy') && ! is_null($resource::$policy)) {
            return $resource;
        }

        return $resource->model() ?? $resource::newModel();
    }

    /**
     * Get the dependent validation rules.
     *
     * @return array<string, string>
     *
     * @see \Illuminate\Validation\Validator::$dependentRules
     */
    public static function dependentRules(string $attribute): array
    {
        return collect([
            'After',
            'AfterOrEqual',
            'Before',
            'BeforeOrEqual',
            'Confirmed',
            'Different',
            'ExcludeIf',
            'ExcludeUnless',
            'ExcludeWith',
            'ExcludeWithout',
            'Gt',
            'Gte',
            'Lt',
            'Lte',
            'AcceptedIf',
            'DeclinedIf',
            'RequiredIf',
            'RequiredUnless',
            'RequiredWith',
            'RequiredWithAll',
            'RequiredWithout',
            'RequiredWithoutAll',
            'Prohibited',
            'ProhibitedIf',
            'ProhibitedUnless',
            'Prohibits',
            'Same',
        ])->mapWithKeys(function ($rule) use ($attribute) {
            $rule = Str::snake($rule);

            return ["{$rule}:" => "{$rule}:{$attribute}."];
        })->all();
    }

    /**
     * Get EOL format from content.
     */
    public static function eol(string $content): string
    {
        $lineEndingCount = [
            "\r\n" => substr_count($content, "\r\n"),
            "\r" => substr_count($content, "\r"),
            "\n" => substr_count($content, "\n"),
        ];

        return array_keys($lineEndingCount, max($lineEndingCount))[0];
    }
}
