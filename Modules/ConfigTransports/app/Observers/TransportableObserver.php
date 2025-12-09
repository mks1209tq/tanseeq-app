<?php

namespace Modules\ConfigTransports\Observers;

use Modules\ConfigTransports\Contracts\Transportable;
use Modules\ConfigTransports\Services\TransportRecorder;

class TransportableObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created(Transportable $model): void
    {
        app(TransportRecorder::class)->recordCreate($model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Transportable $model): void
    {
        app(TransportRecorder::class)->recordUpdate($model);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Transportable $model): void
    {
        app(TransportRecorder::class)->recordDelete($model);
    }
}

