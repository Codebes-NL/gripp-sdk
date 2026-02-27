<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * File resource (read + getContent/uploadContent).
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      string $title              Title.
 * @property      string $previewdatasmall   Preview data (small).
 * @property      string $extendedproperties Extended properties.
 */
class File extends Resource
{
    use CanRead;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'searchname'         => 'string',
        'title'              => 'string',
        'previewdatasmall'   => 'string',
        'extendedproperties' => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    protected static function entity(): string
    {
        return 'file';
    }

    /**
     * Get file content by ID.
     *
     * @param  int $id The file ID.
     * @return array Array containing base64 encoded data and file name.
     */
    public static function getContent(int $id): array
    {
        return static::rpcCall('getContent', [$id])->result();
    }

    /**
     * Upload file content.
     *
     * @param  string $data Base64 encoded file content.
     * @param  string $name File name with extension (e.g. "test.png").
     * @return array  True on success, or error.
     */
    public static function uploadContent(string $data, string $name): array
    {
        return static::rpcCall('uploadContent', [['data' => $data, 'name' => $name]])->result();
    }
}
