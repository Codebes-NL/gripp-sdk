<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Contact resource.
 *
 * Represents contacts linked to companies. Supports full CRUD.
 *
 * @example
 * // Find a contact
 * $contact = Contact::find(456);
 *
 * // Find all contacts for a company
 * $contacts = Contact::where('company', 42)->get();
 *
 * // Create a contact (company FK is required)
 * $result = Contact::create([
 *     'company' => 42,
 *     'firstname' => 'Jan',
 *     'lastname' => 'de Vries',
 *     'email' => 'jan@example.com',
 *     'function' => 'CTO',
 * ]);
 *
 * // Update
 * Contact::update(456, ['phone' => '+31 6 12345678']);
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      string $customfields       Custom fields.
 * @property      string $title              Title.
 * @property      bool   $showoncompanycard  Show on company card.
 * @property      string $dateofbirth        Date of birth.
 * @property      string $notes              Notes.
 * @property      bool   $active             Active status.
 * @property      string $email              Email.
 * @property      string $phone              Phone number.
 * @property      string $mobile             Mobile number.
 * @property      string $department         Department.
 * @property      string $function           Function/role.
 * @property      int    $company            FK → Company (required).
 * @property      string $salutation         Salutation: SIR | MADAM | SIRMADAM.
 * @property      string $initials           Initials.
 * @property      string $firstname          First name.
 * @property      string $infix              Infix.
 * @property      string $lastname           Last name.
 * @property      string $extendedproperties Extended properties.
 * @property      array  $tags               FK[] → Tag.
 */
class Contact extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'customfields'       => 'customfields',
        'searchname'         => 'string',
        'title'              => 'string',
        'showoncompanycard'  => 'boolean',
        'dateofbirth'        => 'date',
        'notes'              => 'string',
        'active'             => 'boolean',
        'email'              => 'string',
        'phone'              => 'string',
        'mobile'             => 'string',
        'department'         => 'string',
        'function'           => 'string',
        'company'            => 'int',
        'salutation'         => 'string',
        'initials'           => 'string',
        'firstname'          => 'string',
        'infix'              => 'string',
        'lastname'           => 'string',
        'extendedproperties' => 'string',
        'tags'               => 'array',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const REQUIRED = [
        'company',
    ];

    public const RELATIONS = [
        'company' => Company::class,
        'tags'    => Tag::class,
    ];

    protected static function entity(): string
    {
        return 'contact';
    }
}
