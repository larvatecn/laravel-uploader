<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Uploader;

use Illuminate\Support\ServiceProvider;

/**
 * 上传服务
 * @author Tongle Xu <xutongle@msn.com>
 */
class UploaderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('uploader', function ($app) {
            return new UploadManager($app);
        });
    }
}
