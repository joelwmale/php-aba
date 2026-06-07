<?php

namespace Joelwmale\PhpAba\Test;

use Joelwmale\PhpAba\PhpAba;

beforeEach(function () {
    $this->aba = new PhpAba;

    $this->descriptiveData = [
        'bank_name' => 'CBA', // bank name
        'user_name' => 'FOO BAR CORPORATION', // Account name, up to 26 characters
        'user_number' => '301500', // direct entry id for CBA
        'description' => 'PAYROLL', // description
        'process_date' => '290616', // DDMMYY
    ];

    $this->detailData = [
        'bsb' => '111-111', // bsb with hyphen
        'account_number' => '999999999',
        'account_name' => 'Jhon doe',
        'reference' => 'Payroll number',
        'remitter' => 'FOO BAR',
        'transaction_code' => '53',
        'trace_bsb' => '111-111',
        'trace_account_number' => '999999999',
        'amount' => '250.87',
    ];
});

describe('phpaba', function () {
    test('add descriptive record', function () {
        $descriptiveData = [
            'bank_name' => 'CBA', // bank name
            'user_name' => 'FOO BAR CORPORATION', // Account name, up to 26 characters
            'user_number' => '301500', // direct entry id for CBA
            'description' => 'PAYROLL', // description
            'process_date' => '290616', // DDMMYY
        ];

        $expectedDescriptiveString = '0                 01CBA       FOO BAR CORPORATION       301500PAYROLL     290616                                        ';

        $descriptiveString = $this->aba->addDescriptiveRecord($descriptiveData);

        expect(substr($descriptiveString, 0, 120))->toEqual($expectedDescriptiveString);
    });

    test('validates process date is the correct format', function () {
        $descriptiveData = [
            'bank_name' => 'CBA', // bank name
            'user_name' => 'FOO BAR CORPORATION', // Account name, up to 26 characters
            'user_number' => '301500', // direct entry id for CBA
            'description' => 'PAYROLL', // description
            'process_date' => '202501', // DDMMYY
        ];

        expect(function () use ($descriptiveData) {
            $this->aba->addDescriptiveRecord($descriptiveData);
        })->toThrow(new \Exception('Process date must be in DDMMMYY format'));
    });

    test('add detail record', function () {
        $detailData = [
            'bsb' => '111-111',
            'account_number' => '999999999',
            'account_name' => 'Jhon doe',
            'reference' => 'Payroll number',
            'remitter' => 'FOO BAR',
            'transaction_code' => '53',
            'trace_bsb' => '111-111',
            'trace_account_number' => '999999999',
            'amount' => '250.87',
        ];

        $expectedDetailString = '1111-111999999999 530000025087Jhon doe                        Payroll number    111-111999999999FOO BAR         00000000';

        $this->aba->addDescriptiveRecord($this->descriptiveData);

        $detailString = $this->aba->addDetailRecord($detailData);

        expect(substr($detailString, 0, 120))->toEqual($expectedDetailString);
    });

    test('add detail record and formats bsb', function () {
        $detailData = [
            'bsb' => '111111',
            'account_number' => '999999999',
            'account_name' => 'Jhon doe',
            'reference' => 'Payroll number',
            'remitter' => 'FOO BAR',
            'transaction_code' => '53',
            'trace_bsb' => '123456',
            'trace_account_number' => '998877665',
            'amount' => '250.87',
        ];

        $expectedDetailString = '1111-111999999999 530000025087Jhon doe                        Payroll number    123-456998877665FOO BAR         00000000';

        $this->aba->addDescriptiveRecord($this->descriptiveData);

        $detailString = $this->aba->addDetailRecord($detailData);

        expect(substr($detailString, 0, 120))->toEqual($expectedDetailString);
    });

    test('add file total record', function () {
        $expectedFileTotalString = '7999-999            000002508700000250870000000000                        000001                                        ';

        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addDetailRecord($this->detailData);

        $fileTotalString = $this->aba->addFileTotalRecord();

        expect(substr($fileTotalString, 0, 120))->toEqual($expectedFileTotalString);
    });

    test('add blank spaces', function () {
        expect($this->aba->addBlankSpaces(3))->toEqual('   ');
    });

    test('pad string', function () {
        $expected = 'Foo       ';

        expect($this->aba->padString('Foo', 10))->toEqual($expected);
    });

    test('dollars to cents', function () {
        $expected = 25065;

        expect($this->aba->dollarsToCents(250.65))->toEqual($expected);
    });

    test('get net total', function () {
        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addDetailRecord($this->detailData);

        $expected = 250.87;

        expect($this->aba->getNetTotal())->toEqual($expected);
    });

    test('add line break', function () {
        expect($this->aba->addLineBreak())->toEqual("\r\n");
    });

    test('addTransaction delegates to addDetailRecord', function () {
        $this->aba->addDescriptiveRecord($this->descriptiveData);

        $result = $this->aba->addTransaction($this->detailData);

        expect($result[0])->toBe('1');
    });

    test('addTransactions adds multiple transactions', function () {
        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addTransactions([$this->detailData, $this->detailData]);

        expect($this->aba->getNetTotal())->toEqual(501.74);
    });

    test('generate produces a three-line ABA file', function () {
        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addDetailRecord($this->detailData);

        $output = $this->aba->generate();

        $lines = array_filter(explode("\r\n", $output), fn ($line) => $line !== '');

        expect(count($lines))->toBe(3);
        expect($lines[0][0])->toBe('0');
        expect($lines[1][0])->toBe('1');
        expect($lines[2][0])->toBe('7');
    });

    test('credit transaction updates totalCreditAmount', function () {
        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addDetailRecord($this->detailData);

        expect($this->aba->getTotalCreditAmount())->toEqual(250.87);
        expect($this->aba->getTotalDebitAmount())->toEqual(0.0);
    });

    test('debit transaction updates totalDebitAmount', function () {
        $debitData = array_merge($this->detailData, ['transaction_code' => '13']);

        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addDetailRecord($debitData);

        expect($this->aba->getTotalDebitAmount())->toEqual(250.87);
        expect($this->aba->getTotalCreditAmount())->toEqual(0.0);
    });

    test('throws on invalid transaction code', function () {
        $invalidData = array_merge($this->detailData, ['transaction_code' => '99']);

        $this->aba->addDescriptiveRecord($this->descriptiveData);

        expect(fn () => $this->aba->addDetailRecord($invalidData))
            ->toThrow(\Exception::class, 'Transaction code is invalid.');
    });

    test('padString truncates value exceeding length', function () {
        expect($this->aba->padString('ABCDEFGHIJK', 5))->toBe('ABCDE');
    });

    test('padString pads left with custom pad string', function () {
        expect($this->aba->padString('123', 6, '0', STR_PAD_LEFT))->toBe('000123');
    });
});
