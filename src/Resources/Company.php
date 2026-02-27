<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Company resource.
 *
 * Represents companies and private persons in Gripp. Supports full CRUD.
 *
 * @example
 * // Find a company by ID
 * $company = Company::find(123);
 *
 * // Search companies
 * $results = Company::where('companyname', 'contains', 'Acme')
 *     ->where('active', true)
 *     ->orderBy('companyname', 'asc')
 *     ->get();
 *
 * // Create a company (relationtype is required)
 * $result = Company::create([
 *     'companyname' => 'New Corp BV',
 *     'relationtype' => 'COMPANY',
 *     'email' => 'info@newcorp.nl',
 * ]);
 *
 * // Update
 * Company::update(123, ['phone' => '+31 20 123 4567']);
 *
 * // Delete
 * Company::delete(123);
 *
 * // Lookup by Chamber of Commerce number
 * $company = Company::getCompanyByCOC('12345678');
 *
 * @property-read string   $createdon                      Created timestamp.
 * @property-read string   $updatedon                      Updated timestamp.
 * @property-read int      $id                             Unique identifier.
 * @property-read string   $searchname                     Search name.
 * @property-read array    $files                          FK[] → File.
 * @property      string   $customfields                   Custom fields.
 * @property      int      $identity                       FK → Identity (Settings > Identities & Templates).
 * @property      string   $website                        Website URL.
 * @property      string   $notes                          Notes.
 * @property      string   $invoicesendto                  Invoice send to: VISITINGADDRESS | POSTADDRESS | OTHER.
 * @property      string   $invoiceaddress_companyname     Invoice address company name.
 * @property      string   $invoiceaddress_attn            Invoice address attention.
 * @property      string   $invoiceaddress_street          Invoice address street.
 * @property      string   $invoiceaddress_streetnumber    Invoice address street number.
 * @property      string   $invoiceaddress_addressline2    Invoice address line 2.
 * @property      string   $invoiceaddress_zipcode         Invoice address zip code.
 * @property      string   $invoiceaddress_city            Invoice address city.
 * @property      string   $invoiceaddress_country         Invoice address country.
 * @property      int      $customernumber                 Customer number.
 * @property      string   $bankaccount                    Bank account (IBAN).
 * @property      string   $bankascription                 Bank ascription.
 * @property      string   $bankcity                       Bank city.
 * @property      string   $bic                            BIC code.
 * @property      string   $externalreference              External reference.
 * @property      int      $termofpayment                  Term of payment (days).
 * @property      int      $termofpayment_purchaseinvoice  Term of payment for purchase invoices.
 * @property      string   $invoicesendby                  Invoice send by: POST | EMAIL | SIMPLEINVOICING.
 * @property      string   $invoiceemail                   Invoice email (conditionally required).
 * @property      string   $vatnumber                      VAT number.
 * @property      bool     $vatshifted                     VAT shifted.
 * @property      int      $vat                            FK → VAT rate (Settings > BTW-tarieven).
 * @property      string   $cocnumber                      Chamber of Commerce number.
 * @property      string   $dateofbirth                    Date of birth.
 * @property      string   $foundationdate                 Foundation date.
 * @property      bool     $active                         Active status.
 * @property      string   $postaddress                    Post address: VISITINGADDRESS | OTHER.
 * @property      string   $postaddress_street             Post address street.
 * @property      string   $postaddress_streetnumber       Post address street number.
 * @property      string   $postaddress_addressline2       Post address line 2.
 * @property      string   $postaddress_zipcode            Post address zip code.
 * @property      string   $postaddress_city               Post address city.
 * @property      string   $postaddress_country            Post address country.
 * @property      string   $visitingaddress_street         Visiting address street.
 * @property      string   $visitingaddress_streetnumber   Visiting address street number.
 * @property      string   $visitingaddress_addressline2   Visiting address line 2.
 * @property      string   $visitingaddress_zipcode        Visiting address zip code.
 * @property      string   $visitingaddress_city           Visiting address city.
 * @property      string   $visitingaddress_country        Visiting address country.
 * @property      string   $visitingaddress_lng            Visiting address longitude.
 * @property      string   $visitingaddress_lat            Visiting address latitude.
 * @property      string   $email                          Email.
 * @property      string   $phone                          Phone number.
 * @property      string   $mobile                         Mobile number.
 * @property      string   $relationtype                   Relation type: COMPANY | PRIVATEPERSON (required).
 * @property      int      $accountmanager                 FK → Employee.
 * @property      string   $companyname                    Company name (conditionally required).
 * @property      string   $legalname                      Legal name (conditionally required).
 * @property      string   $salutation                     Salutation: SIR | MADAM | SIRMADAM.
 * @property      string   $initials                       Initials.
 * @property      string   $title                          Title.
 * @property      string   $firstname                      First name.
 * @property      string   $infix                          Infix.
 * @property      string   $lastname                       Last name (conditionally required).
 * @property      string   $screenname                     Screen name.
 * @property      string   $extendedproperties             Extended properties.
 * @property      string   $paymentmethod                  Payment method: MANUALTRANSFER | AUTOMATICTRANSFER.
 * @property      string   $paymentmethod_purchaseinvoice  Payment method purchase invoice: MANUALTRANSFER | AUTOMATICTRANSFER.
 * @property      array    $tags                           FK[] → Tag.
 * @property      array    $companyroles                   Company roles: LEAD | CUSTOMER | SUPPLIER | PROSPECT.
 */
class Company extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'                     => 'datetime',
        'updatedon'                     => 'datetime',
        'id'                            => 'int',
        'customfields'                  => 'customfields',
        'searchname'                    => 'string',
        'identity'                      => 'int',
        'website'                       => 'string',
        'notes'                         => 'string',
        'invoicesendto'                 => 'string',
        'invoiceaddress_companyname'    => 'string',
        'invoiceaddress_attn'           => 'string',
        'invoiceaddress_street'         => 'string',
        'invoiceaddress_streetnumber'   => 'string',
        'invoiceaddress_addressline2'   => 'string',
        'invoiceaddress_zipcode'        => 'string',
        'invoiceaddress_city'           => 'string',
        'invoiceaddress_country'        => 'string',
        'customernumber'                => 'int',
        'bankaccount'                   => 'string',
        'bankascription'                => 'string',
        'bankcity'                      => 'string',
        'bic'                           => 'string',
        'externalreference'             => 'string',
        'termofpayment'                 => 'int',
        'termofpayment_purchaseinvoice' => 'int',
        'invoicesendby'                 => 'string',
        'invoiceemail'                  => 'string',
        'vatnumber'                     => 'string',
        'vatshifted'                    => 'boolean',
        'vat'                           => 'int',
        'cocnumber'                     => 'string',
        'dateofbirth'                   => 'date',
        'foundationdate'                => 'date',
        'active'                        => 'boolean',
        'postaddress'                   => 'string',
        'postaddress_street'            => 'string',
        'postaddress_streetnumber'      => 'string',
        'postaddress_addressline2'      => 'string',
        'postaddress_zipcode'           => 'string',
        'postaddress_city'              => 'string',
        'postaddress_country'           => 'string',
        'visitingaddress_street'        => 'string',
        'visitingaddress_streetnumber'  => 'string',
        'visitingaddress_addressline2'  => 'string',
        'visitingaddress_zipcode'       => 'string',
        'visitingaddress_city'          => 'string',
        'visitingaddress_country'       => 'string',
        'visitingaddress_lng'           => 'string',
        'visitingaddress_lat'           => 'string',
        'email'                         => 'string',
        'phone'                         => 'string',
        'mobile'                        => 'string',
        'relationtype'                  => 'string',
        'accountmanager'                => 'int',
        'companyname'                   => 'string',
        'legalname'                     => 'string',
        'salutation'                    => 'string',
        'initials'                      => 'string',
        'title'                         => 'string',
        'firstname'                     => 'string',
        'infix'                         => 'string',
        'lastname'                      => 'string',
        'screenname'                    => 'string',
        'extendedproperties'            => 'string',
        'paymentmethod'                 => 'string',
        'paymentmethod_purchaseinvoice' => 'string',
        'tags'                          => 'array',
        'companyroles'                  => 'array',
        'files'                         => 'array',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'files',
    ];

    const REQUIRED = [
        'relationtype',
    ];

    const RELATIONS = [
        'accountmanager' => Employee::class,
        'tags'           => Tag::class,
        'files'          => File::class,
    ];

    protected static function entity(): string
    {
        return 'company';
    }

    public static function getCompanyByCOC(string $coc): array
    {
        return static::rpcCall('getCompanyByCOC', [$coc])->result();
    }

    /**
     * Add an interaction to a company by ID.
     *
     * @param  int    $companyId      The company ID.
     * @param  string $interactionKey The interaction key (configured in the application).
     * @param  array  $customfields   Custom fields for the interaction.
     * @return array  True on success, or error.
     */
    public static function addInteractionByCompanyId(int $companyId, string $interactionKey, array $customfields): array
    {
        return static::rpcCall('addInteractionByCompanyId', [$companyId, $interactionKey, $customfields])->result();
    }

    /**
     * Add an interaction to a company by COC number.
     *
     * @param  string $coc            The COC number of the company.
     * @param  string $interactionKey The interaction key (configured in the application).
     * @param  array  $customfields   Custom fields for the interaction.
     * @return array  True on success, or error.
     */
    public static function addInteractionByCompanyCOC(string $coc, string $interactionKey, array $customfields): array
    {
        return static::rpcCall('addInteractionByCompanyCOC', [$coc, $interactionKey, $customfields])->result();
    }
}
