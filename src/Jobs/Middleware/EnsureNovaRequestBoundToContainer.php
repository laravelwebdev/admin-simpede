<?php

namespace Laravel\Nova\Jobs\Middleware;

use Closure;
use Laravel\Nova\Http\Requests\NovaRequest;

class EnsureNovaRequestBoundToContainer
{
    /**
     * Process the queued job.
     *
     * @param  \Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $boundedByMiddleware = false;

        if (optional($job)->request instanceof NovaRequest) {
            if (! app()->bound(NovaRequest::class)) {
                app()->instance(NovaRequest::class, $job->request);
                $boundedByMiddleware = true;
            }
        }

        $next($job);

        if ($boundedByMiddleware) {
            app()->forgetInstance(NovaRequest::class);
        }
    }
}
