<?php

namespace Joelwmale\PhpAba\Validators;

use Exception;

class PhpAbaValidator
{
    /**
     * Transaction codes
     *
     * @var array
     */
    public static $transactionCodes = [
        'externally_initiated_debit' => '13',
        'externally_initiated_credit' => '50',
        'australian_government_security_interest ' => '51',
        'family_allowance' => '52',
        'pay' => '53',
        'pension' => '54',
        'allotment' => '55',
        'dividend' => '56',
        'debenture' => '57',
        'note_interest' => '57',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $descriptiveRecordRules = [
        'bank_name' => ['required', 'regex:/^[A-Z]{3}$/'],
        'user_name' => 'required|regex:/^[A-Za-z\s+]{0,26}$/',

        'user_number' => ['required', 'regex:/^[\d]{0,6}$/'],
        'description' => ['required', 'regex:/^[A-Za-z\s]{0,12}$/'],

        'process_date' => ['required', 'regex:/^[\d]{6}$/', 'date_format:dmy'],
    ];

    protected static $detailRecordRules = [
        'transaction_code' => ['required', 'regex:/^[\d]{2}$/'],

        'bsb' => ['required', 'regex:/^[\d]{3}-[\d]{3}$|^[\d]{6}$/'],
        'account_number' => ['required', 'regex:/^[\d]{0,9}$/'],

        'account_name' => ['required', 'regex:/^[A-Za-z0-9^_[\]\',?;:=#\/.*()&%!$ @+-]{0,32}$/'],

        'amount' => ['required'],
        'withholding_tax' => ['numeric', 'regex:/^[\d]{0,10}$/'],

        'remitter' => ['required', 'regex:/^[A-Za-z\s+]{0,16}$/'],
    ];

    /**
     * Error messages
     *
     * @var array
     */
    protected static $messages = [
        'bsb.required' => 'BSB is required',
        'bsb.regex' => 'BSB format is incorrect. The valid format is XXX-XXX or XXXXXX',
        'account_number.required' => 'Account number is required',
        'account_number.regex' => 'Account number must be up to 9 digits',
        'bank_name.required' => 'Bank name is required',
        'bank_name.regex' => 'Bank name must be 3 characters long and Capitalised',
        'user_name.required' => 'User name is required',
        'user_name.regex' => 'User or preferred name must be letters only and up to 26 characters long',
        'account_name' => 'Account name must be english characters and a maximum of 32 characters long',
        'user_number.required' => 'User number is required',
        'user_number.regex' => 'User number which is allocated by APCA must be up to 6 digits long. The Commonwealth bank default is 301500',
        'description.required' => 'Description is required',
        'description.regex' => 'Description must be up to 12 characters long and letters only',
        'indicator' => 'The Indicator is invalid. Must be one of N, W, X, Y or otherwise blank filled.',
        'reference' => 'The reference must be BECS characters and up to 18 characters long and . For example: Payroll number',
        'remitter' => 'The remitter must be letters only and up to 16 characters long.',
        'process_date.required' => 'Process date is required',
        'process_date.regex' => 'Process date must be in DDMMMYY format',
        'amount.required' => 'Amount is required',
        'amount.regex' => 'Amount must be a numeric number and up to 10 digits long',
        'process_date.date_format' => 'Process date must be in DDMMMYY format',
    ];

    public static function validateDescriptiveRecord(array $record)
    {
        $validator = Validator::make($record, self::$descriptiveRecordRules, self::$messages);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return true;
    }

    public static function validateDetailRecord(array $record)
    {
        $validator = Validator::make($record, self::$detailRecordRules, self::$messages);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return true;
    }

    /**
     * Check any required fields is missing
     *
     * @param  string  $recordType
     * @return void
     */
    public static function verifyRecord(array $record, array $matchRules, $recordType = 'Detail')
    {
        $missingFields = array_diff($matchRules, array_keys($record));

        if ($missingFields) {
            throw new Exception("Some required {$recordType} fields missing: ".implode(',', $missingFields));
        }

        return true;
    }

    /**
     * Validate a transaction code
     *
     * @param  string  $code
     * @return void
     */
    public static function validateTransactionCode($code)
    {
        if (! in_array($code, self::$transactionCodes)) {
            throw new Exception('Transaction code is invalid.');
        }

        return true;
    }

    /**
     * Check a number is numeric or not
     *
     * @param  float  $value
     * @return void
     */
    public static function validateNumeric($value)
    {
        if (! is_numeric($value)) {
            throw new Exception('Amount or Withholding tax amount must be a numeric number');
        }

        return true;
    }
}
