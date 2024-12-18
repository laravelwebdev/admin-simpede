<?php

namespace Laravel\Nova\Actions;

use ArrayAccess;
use JsonSerializable;
use Laravel\Nova\URL;
use Stringable;

class ActionResponse implements ArrayAccess, JsonSerializable
{
    private ?string $danger = null;

    private ?bool $deleted = null;

    private ?string $download = null;

    private Stringable|string|null $message = null;

    private Stringable|string|null $name = null;

    private bool $openInNewTab = false;

    private ?string $redirect = null;

    /**
     * @var array{path: string, options: array<string, mixed>}|null
     */
    private ?array $visit = null;

    private ?string $modal = null;

    private array $data = [];

    /**
     * @return static
     */
    public static function message(Stringable|string $message)
    {
        return tap(new static, function ($response) use ($message) {
            $response->withMessage($message);
        });
    }

    /**
     * @return $this
     */
    public function withMessage(Stringable|string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return static
     */
    public static function danger(Stringable|string $message)
    {
        return tap(new static, function ($response) use ($message) {
            $response->withDangerMessage($message);
        });
    }

    /**
     * @return $this
     */
    public function withDangerMessage(Stringable|string $message)
    {
        $this->danger = $message;

        return $this;
    }

    /**
     * @return $this
     */
    public function withDeleted()
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * @return static
     */
    public static function deleted()
    {
        return tap(new static, function ($response) {
            $response->withDeleted();
        });
    }

    /**
     * @return static
     */
    public static function redirect(string $url)
    {
        return tap(new static, function ($response) use ($url) {
            $response->withRedirect($url);
        });
    }

    /**
     * @return $this
     */
    public function withRedirect(string $url)
    {
        $this->redirect = $url;

        return $this;
    }

    /**
     * @return static
     */
    public static function openInNewTab(string $url)
    {
        return static::redirect($url)->usingNewTab();
    }

    /**
     * @return $this
     */
    public function usingNewTab()
    {
        $this->openInNewTab = true;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return static
     */
    public static function visit(URL|string $path, array $options = [])
    {
        return tap(new static, function ($response) use ($path, $options) {
            $response->withVisitOptions($path, $options);
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @return $this
     */
    public function withVisitOptions(URL|string $path, array $options = [])
    {
        $this->visit = [
            'path' => '/'.ltrim($path, '/'),
            'options' => $options,
        ];

        return $this;
    }

    /**
     * @return static
     */
    public static function download(Stringable|string $name, string $url)
    {
        return tap(new static, function ($response) use ($name, $url) {
            $response->withDownload($name, $url);
        });
    }

    /**
     * @return $this
     */
    public function withDownload(Stringable|string $name, string $url)
    {
        $this->name = $name;
        $this->download = $url;

        return $this;
    }

    /**
     * @return static
     */
    public static function modal(string $modal, array $data)
    {
        return tap(new static, function ($response) use ($data, $modal) {
            $response->withModal($modal, $data);
        });
    }

    /**
     * @return $this
     */
    public function withModal(string $modal, array $data = [])
    {
        $this->modal = $modal;
        $this->data = $data;

        return $this;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->{$offset});
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->{$offset};
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetSet($offset, $value): void
    {
        if (property_exists($this, $offset)) {
            $this->{$offset} = $value;
        }
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->{$offset});
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(array_merge([
            'danger' => $this->danger,
            'deleted' => $this->deleted,
            'download' => $this->download,
            'modal' => $this->modal,
            'message' => $this->message,
            'name' => $this->name,
            'openInNewTab' => $this->openInNewTab,
            'redirect' => $this->redirect,
            'visit' => $this->visit,
        ], $this->data));
    }
}
