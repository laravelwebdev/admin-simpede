<?php

namespace Laravel\Nova\Fields\Attachments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $attachment
 * @property string $disk
 */
class Attachment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nova_field_attachments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * Purge the attachment.
     *
     * @return void
     */
    public function purge()
    {
        Storage::disk($this->disk)->delete($this->attachment);

        $this->delete();
    }
}
