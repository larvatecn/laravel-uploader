<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Uploader;

use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderAdapter
{
    public const NAME_UNIQUE = 'unique';
    public const NAME_DATETIME = 'datetime';
    public const NAME_SEQUENCE = 'sequence';
    public const NAME_MD5 = 'md5';
    public const NAME_HASH = 'hash';

    public const DIRECTORY_FILE = 'files';
    public const DIRECTORY_IMAGE = 'images';

    /**
     * Upload directory.
     *
     * @var string
     */
    protected string $directory = '';

    /**
     * File name.
     *
     * @var string|callable|null
     */
    protected $name = null;

    /**
     * Storage instance.
     *
     * @var Filesystem
     */
    protected Filesystem $storage;

    /**
     * Use (unique or datetime or sequence) name for store upload file.
     *
     * @var string|null
     */
    protected ?string $generateName = null;

    /**
     * Controls the storage permission. Could be 'private' or 'public'.
     *
     * @var string|null
     */
    protected ?string $visibility = null;

    /**
     * Constructor.
     * @param Filesystem $storage
     */
    public function __construct(Filesystem $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Default directory for file to upload.
     *
     * @return string
     */
    public function defaultDirectory(): string
    {
        return static::DIRECTORY_FILE;
    }

    /**
     * 设置上传文件跟目录
     *
     * @param string|callable $dir
     * @return $this
     */
    public function dir($dir)
    {
        if ($dir) {
            $this->directory = $dir;
        }
        return $this;
    }

    /**
     * 设置文件名称
     *
     * @param string|callable $name
     * @return $this
     */
    public function name($name)
    {
        if ($name) {
            $this->name = $name;
        }
        return $this;
    }

    /**
     * Use unique name for store upload file.
     *
     * @return $this
     */
    public function uniqueName()
    {
        $this->generateName = static::NAME_UNIQUE;
        return $this;
    }

    /**
     * Use datetime name for store upload file.
     *
     * @return $this
     */
    public function datetimeName()
    {
        $this->generateName = static::NAME_DATETIME;
        return $this;
    }

    /**
     * Use sequence name for store upload file.
     *
     * @return $this
     */
    public function sequenceName()
    {
        $this->generateName = static::NAME_SEQUENCE;
        return $this;
    }

    /**
     * Use md5 name for store upload file.
     *
     * @return $this
     */
    public function md5Name()
    {
        $this->generateName = static::NAME_MD5;
        return $this;
    }

    /**
     * Use hash name for store upload file.
     *
     * @return $this
     */
    public function hashName()
    {
        $this->generateName = static::NAME_HASH;
        return $this;
    }

    /**
     * Get getStorage.
     *
     * @return Filesystem|\League\Flysystem\Filesystem
     */
    public function getStorage(): Filesystem|\League\Flysystem\Filesystem
    {
        return $this->storage;
    }

    /**
     * 获取文件存储目录
     *
     * @return string
     */
    public function getDirectory(): string
    {
        return ($this->directory ?: $this->defaultDirectory());
    }

    /**
     * Get file visit url.
     *
     * @param string $path
     * @return string
     */
    public function url(string $path)
    {
        if (URL::isValidUrl($path)) {
            return $path;
        }
        return $this->storage->url($path);
    }

    /**
     * 获取临时下载地址
     * @param string $file
     * @param \DateTimeInterface $expiration 链接有效期
     * @return string
     */
    public function temporaryUrl(string $file, \DateTimeInterface $expiration): string
    {
        if ($this->storage instanceof CloudFilesystemContract) {
            try {
                return $this->storage->temporaryUrl($file, $expiration);
            } catch (\RuntimeException $exception) {
                Log::error($exception->getMessage(), $exception->getTrace());
            }
        }
        return $this->url($file);
    }

    /**
     * 销毁原始文件
     *
     * @param string|null $path
     * @return true
     */
    public function destroy(string $path = null): bool
    {
        if (!$path) {
            return true;
        }
        if (URL::isValidUrl($path)) {
            $path = parse_url($path, PHP_URL_PATH);
        }
        if (!empty($path) && $this->storage->exists($path)) {
            return $this->storage->delete($path);
        }
        return true;
    }

    /**
     * Set file permission when stored into storage.
     *
     * @param string $visibility
     * @return $this
     */
    public function visibility(string $visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * 获取文件存储名称
     *
     * @param File $file
     * @return string|null
     */
    public function getStoreName(File $file): ?string
    {
        if ($this->generateName == static::NAME_UNIQUE) {
            return $this->generateUniqueName($file);
        } elseif ($this->generateName == static::NAME_DATETIME) {
            return $this->generateDatetimeName($file);
        } elseif ($this->generateName == static::NAME_SEQUENCE) {
            return $this->generateSequenceName($file);
        } elseif ($this->generateName == static::NAME_MD5) {
            return $this->generateMd5Name($file);
        } elseif ($this->generateName == static::NAME_HASH) {
            return $this->generateHashName($file);
        }

        if ($this->name instanceof \Closure) {
            return call_user_func_array($this->name, [$this, $file]);
        }

        if (is_string($this->name)) {
            return $this->name;
        }

        return $this->generateClientName($file);
    }

    /**
     * Upload file and delete original file.
     *
     * @param UploadedFile $file
     * @return string|false
     */
    public function upload(UploadedFile $file): bool|string
    {
        $this->name = $this->getStoreName($file);
        $this->renameIfExists($file);
        if (!is_null($this->visibility)) {
            return $this->storage->putFileAs($this->getDirectory(), $file, $this->name, $this->visibility);
        }
        return $this->storage->putFileAs($this->getDirectory(), $file, $this->name);
    }

    /**
     * 保存本地文件
     * @param string $file
     * @return false|string
     */
    public function store(string $file): bool|string
    {
        $file = new \Illuminate\Http\File($file);
        $this->name = $this->getStoreName($file);
        if (!is_null($this->visibility)) {
            return $this->storage->putFileAs($this->getDirectory(), $file, $this->name, $this->visibility);
        }
        return $this->storage->putFileAs($this->getDirectory(), $file, $this->name);
    }

    /**
     * If name already exists, rename it.
     *
     * @param File $file
     * @return void
     */
    public function renameIfExists(File $file)
    {
        if ($this->storage->exists("{$this->getDirectory()}/$this->name")) {
            $this->name = $this->generateUniqueName($file);
        }
    }

    /**
     * Generate a unique name for uploaded file.
     *
     * @param File $file
     * @return string
     */
    public function generateUniqueName(File $file): string
    {
        return md5(uniqid() . microtime()) . '.' . $this->getClientOriginalExtension($file);
    }

    /**
     * Generate a datetime name for uploaded file.
     *
     * @param File $file
     * @return string
     */
    public function generateDatetimeName(File $file): string
    {
        return date('YmdHis') . mt_rand(10000, 99999) . '.' . $this->getClientOriginalExtension($file);
    }

    /**
     * Generate a md5 name for uploaded file.
     *
     * @param File $file
     * @return string
     */
    public function generateMd5Name(File $file): string
    {
        return md5_file($file->getPathname()) . '.' . $this->getClientOriginalExtension($file);
    }

    /**
     * Generate a hash name for uploaded file.
     *
     * @param File $file
     * @return string
     */
    public function generateHashName(File $file): string
    {
        return sha1_file($file->getPathname()) . '.' . $this->getClientOriginalExtension($file);
    }

    /**
     * Generate a sequence name for uploaded file.
     *
     * @param File $file
     * @return string
     */
    public function generateSequenceName(File $file): string
    {
        $index = 1;
        $original = $this->generateClientName($file);
        $extension = $this->getClientOriginalExtension($file);
        $new = sprintf('%s_%s.%s', $original, $index, $extension);
        while ($this->storage->exists("{$this->getDirectory()}/$new")) {
            $index++;
            $new = sprintf('%s_%s.%s', $original, $index, $extension);
        }
        return $new;
    }

    /**
     * Use file'oldname for uploaded file.
     *
     * @param File $file
     * @return string
     */
    public function generateClientName(File $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }
        return $file->getFilename();
    }

    /**
     * 获取文件后缀
     * @param File $file
     * @return string
     */
    protected function getClientOriginalExtension(File $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalExtension();
        } else {
            return $file->getExtension();
        }
    }
}
