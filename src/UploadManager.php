<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Uploader;

class UploadManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new filesystem manager instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Resolve the given disk.
     *
     * @param string|null $disk
     * @return UploaderAdapter
     */
    protected function disk(string $disk = null): UploaderAdapter
    {
        return new UploaderAdapter($disk);
    }
}
