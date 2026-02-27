<?php

namespace CodeBes\GrippSdk\Resources;

/**
 * Notification resource (emit/emitall only, no CRUD).
 *
 * Used to send in-app notifications to employees.
 */
class Notification extends Resource
{
    protected static function entity(): string
    {
        return 'notification';
    }

    /**
     * Send a notification to specific employees.
     *
     * @param  array  $employeeIds  Array of employee IDs to notify.
     * @param  string $title        The title of the message.
     * @param  string $body         The body of the message.
     * @param  string $eventType    Must be NOTIFICATION | WARNING | ERROR | SYSTEMMESSAGE.
     * @return array  True or errors.
     */
    public static function emit(array $employeeIds, string $title, string $body, string $eventType): array
    {
        return static::rpcCall('emit', [$employeeIds, $title, $body, $eventType])->result();
    }

    /**
     * Send a notification to all employees.
     *
     * @param  string $title     The title of the message.
     * @param  string $body      The body of the message.
     * @param  string $eventType Must be NOTIFICATION | WARNING | ERROR | SYSTEMMESSAGE.
     * @return array  True or errors.
     */
    public static function emitall(string $title, string $body, string $eventType): array
    {
        return static::rpcCall('emitall', [$title, $body, $eventType])->result();
    }
}
