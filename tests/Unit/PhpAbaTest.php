<?php

namespace Joelwmale\PhpAba\Test;

use Joelwmale\PhpAba\PhpAba;

beforeEach(function () {
    $this->aba = new PhpAba;

    $this->descriptiveData = [
        'bsb' => '062-111', // bsb
        'account_number' => '111111111', // account number
        'bank_name' => 'CBA', // bank name
        'user_name' => 'FOO BAR CORPORATION', // Account name, up to 26 characters
        // 'remitter' => 'FOO BAR', // Remitter
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
        'amount' => '250.87',
    ];
});

describe('phpaba', function () {
    test('add descriptive record', function () {
        $expectedDescriptiveString = '0                 01CBA       FOO BAR CORPORATION       301500PAYROLL     290616                                        ';

        $descriptiveString = $this->aba->addDescriptiveRecord($this->descriptiveData);

        expect(substr($descriptiveString, 0, 120))->toEqual($expectedDescriptiveString);
    });

    test('add detail record', function () {
        $expectedDetailString = '1111-111999999999 530000025087Jhon doe                        Payroll number    111-111999999999FOO BAR         00000000';

        $this->aba->addDescriptiveRecord($this->descriptiveData);

        $detailString = $this->aba->addDetailRecord($this->detailData);

        // Total detail record would be 120 characters
        // remove line break
        $detailString = substr($detailString, 0, 120);

        expect($detailString)->toEqual($expectedDetailString);
    });

    test('add detail record and formats bsb', function () {
        $expectedDetailString = '1111-111999999999 530000025087Jhon doe                        Payroll number    111-111999999999FOO BAR         00000000';

        $descriptiveData = $this->descriptiveData;
        $descriptiveData['bsb'] = '111111';

        $this->aba->addDescriptiveRecord($this->descriptiveData);

        $detailString = $this->aba->addDetailRecord($this->detailData);

        expect(substr($detailString, 0, 120))->toEqual($expectedDetailString);
    });

    test('add file total record', function () {
        $expectedFileTotalString = '7999-999            000002508700000250870000000000                        000001                                        ';

        $this->aba->addDescriptiveRecord($this->descriptiveData);
        $this->aba->addDetailRecord($this->detailData);

        $fileTotalString = $this->aba->addFileTotalRecord();

        // Total detail record would be 120 characters
        // remove line break
        $fileTotalString = substr($fileTotalString, 0, 120);

        expect($fileTotalString)->toEqual($expectedFileTotalString);
    });

    test('add blank spaces', function () {
        expect($this->aba->addBlankSpaces(3))->toEqual('   ');
    });

    test('pad string', function () {
        $expected = 'Foo Bar   ';

        expect($this->aba->padString('Foo Bar', 10))->toEqual($expected);
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
});
