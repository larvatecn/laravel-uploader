<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Uploader;

use Illuminate\Support\Facades\Facade;

/**
 * 上传助手
 * @method static UploaderAdapter disk(string $disk=null)
 * @author Tongle Xu <xutongle@msn.com>
 */
class Uploader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'uploader';
    }
}
