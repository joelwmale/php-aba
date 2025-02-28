<a name="top"></a>
[![Latest Version on Packagist](https://img.shields.io/packagist/v/joelwmale/php-aba.svg?style=flat-square)](https://packagist.org/packages/joelwmale/php-aba)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/joelwmale/php-aba/tests.yml?branch=master&label=Tests)](https://github.com/joelwmale/php-aba/actions?query=workflow%3ATests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/joelwmale/php-aba.svg?style=flat-square)](https://packagist.org/packages/joelwmale/php-aba)
[![GitHub last commit](https://img.shields.io/github/last-commit/joelwmale/php-aba)](#)
[![License](https://poser.pugx.org/joelwmale/php-aba/license.svg)](https://packagist.org/packages/joelwmale/php-aba)
[![Free](https://img.shields.io/badge/free_for_non_commercial_use-brightgreen)](#-license)

# PHP ABA

Provides a simple way to generate an ABA file which can be used to mass import payments into Australian banks.

## Features

- Simple API
- Laravel support via a service provider and facade
- Framework agnostic

## Requirements

- PHP 8+

## 🚀 Getting Started
### 🔥 Installing

Install the package through [Composer](http://getcomposer.org/).

`composer require joelwmale/php-aba`

## Integrations

##### Laravel integrations
Although `Aba` is framework agnostic, it does support Laravel out of the box and comes with a Service provider and Facade for easy integration.

After you have installed the `Aba`, open the `config/app.php` file which is included with Laravel and add the following lines.

In the `$providers` array add the following service provider.

```php
Joelwmale\PhpAba\AbaServiceProvider::class
```

Add the facade of this package to the `$aliases` array.

```php
'Aba' => Joelwmale\PhpAba\Facades\Aba::class,
```

You can now use this facade in place of instantiating the converter yourself in the following examples.

## 🧑‍🍳 Demo

```php
use Joelwmale\PhpAba\PhpAba;

$aba = new PhpAba();

// descriptive record or file header
$aba->addFileDetails([
    'bank_name' => 'ANZ', // bank name
    'user_name' => 'John Doe', // account name or company
    'user_number' => '301500', // user number (as allocated by APCA).
    'description' => 'Payroll', // description
    'process_date'  => '010125' // DDMMYY - date for it to be processed by the bank
]);

// now you can add transactions
$aba->addTransaction([
    'bsb' => '111-111',
    'account_number' => '999999999',
    'account_name'  => 'Jhon doe',
    'reference' => 'Payroll number',
    'remitter' => 'John Doe', // Remitter
    'transaction_code'  => '53',
    'amount' => '250.87' // must be in whole dollars
]);

$abaFileContent = $aba->generate(); // generate the ABA file

// now store it somewhere, or download it
```

## 📚 Documentation

### Mutiple transactions
```php
$transactions = [
    [
        'bsb' => '111-111',
        'account_number' => '999999999',
        'account_name' => 'John Doe',
        'reference' => 'Wages',
        'transaction_code' => '53',
        'amount' => '250.87'
    ],
    [
        'bsb' => '222-2222',
        'account_number' => '888888888',
        'account_name'  => 'Jane Doe',
        'reference' => 'Rent',
        'transaction_code'  => '50',
        'amount' => '300.01'
    ]
];

$aba->addTransactions($transaction);

$aba->generate();
```

### Notes

#### Field Descriptions & Values

<table cellpadding="5" cellspacing="0">
    <tbody>
        <tr>
            <td>Field</td>
            <td>Description</td>
        </tr>
        <tr>
            <td>Bank name</td>
            <td>Bank name must be 3 characters long and Capitalised. For example: CBA</td>
        </tr>
        <tr>
            <td>BSB</td>
            <td>The valid BSB format is XXX-XXX.</td>
        </tr>
        <tr>
            <td>Account number</td>
            <td>Account number must be up to 9 digits.</td>
        </tr>
        <tr>
            <td>User name (Descriptive record)</td>
            <td>User or preferred name must be letters only and up to 26 characters long.</td>
        </tr>
        <tr>
            <td>Account name (Detail record)</td>
            <td>Account name must be BECS characters only and up to 32 characters long.</td>
        </tr>
        <tr>
            <td>User number</td>
            <td>User number which is allocated by APCA must be up to 6 digits long. The Commonwealth bank default is 301500.</td>
        </tr>
        <tr>
            <td>Description (Descriptive record)</td>
            <td>Description must be up to 12 characters long and letters only.</td>
        </tr>
        <tr>
            <td>Reference (Detail record)</td>
            <td>The reference must be BECS characters only and up to 18 characters long. For example: Payroll number.</td>
        </tr>
        <tr>
            <td>Remitter</td>
            <td>The remitter must be letters only and up to 16 characters long.</td>
        </tr>
    </tbody>
</table>

#### Transaction codes
<table cellpadding="5" cellspacing="0">
    <tbody>
        <tr>
            <td>Code</td>
            <td>Transaction Description</td>
        </tr>
        <tr>
            <td>13</td>
            <td>Externally initiated debit items</td>
        </tr>
        <tr>
            <td>50</td>
            <td>Externally initiated credit items with the exception of those bearing Transaction Codes</td>
        </tr>
        <tr>
            <td>51</td>
            <td>Australian Government Security Interest</td>
        </tr>
        <tr>
            <td>52</td>
            <td>Family Allowance</td>
        </tr>
        <tr>
            <td>53</td>
            <td>Pay</td>
        </tr>
        <tr>
            <td>54</td>
            <td>Pension</td>
        </tr>
        <tr>
            <td>55</td>
            <td>Allotment</td>
        </tr>
        <tr>
            <td>56</td>
            <td>Dividend</td>
        </tr>
        <tr>
            <td>57</td>
            <td>Debenture/Note Interest</td>
        </tr>
    </tbody>
</table>

## Reference
- [https://www.cemtexaba.com/aba-format/cemtex-aba-file-format-details/](https://www.cemtexaba.com/aba-format/cemtex-aba-file-format-details/)
- [https://www.bcu.com.au/business-banking/payments/internet-banking/aba-file-validator/](https://www.bcu.com.au/business-banking/payments/internet-banking/aba-file-validator/)