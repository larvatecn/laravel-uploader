<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Uploader;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;

class UploadManager
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new filesystem manager instance.
     *
     * @param Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Resolve the given disk.
     *
     * @param string|null $disk
     * @return UploaderAdapter
     */
    public function disk(string $disk = null): UploaderAdapter
    {
        return new UploaderAdapter(Storage::disk($disk));
    }
}
