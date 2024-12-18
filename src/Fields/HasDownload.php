<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\Response;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HasDownload
{
    /**
     * The callback used to generate the download HTTP response.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Laravel\Nova\Resource, ?string, ?string):(mixed))|null
     */
    public $downloadResponseCallback;

    /**
     * Determine if the file is able to be downloaded.
     *
     * @var bool
     */
    public $downloadsAreEnabled = true;

    /**
     * Disable downloading the file.
     *
     * @return $this
     */
    public function disableDownload()
    {
        $this->downloadsAreEnabled = false;

        return $this;
    }

    /**
     * Specify the callback that should be used to create a download HTTP response.
     *
     * @param  callable(\Laravel\Nova\Http\Requests\NovaRequest, \Laravel\Nova\Resource, ?string, ?string):mixed  $downloadResponseCallback
     * @return $this
     */
    public function download(callable $downloadResponseCallback)
    {
        $this->downloadResponseCallback = $downloadResponseCallback;

        return $this;
    }

    /**
     * Create an HTTP response to download the underlying field.
     */
    public function toDownloadResponse(NovaRequest $request, Resource $resource): Response|StreamedResponse
    {
        return call_user_func(
            $this->downloadResponseCallback,
            $request,
            $resource->resource,
            $this->getStorageDisk(),
            $this->getStoragePath()
        );
    }
}
