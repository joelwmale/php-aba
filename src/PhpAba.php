<?php

namespace Joelwmale\PhpAba;

use Joelwmale\PhpAba\Validators\PhpAbaValidator;

class PhpAba
{
    public const DESCRIPTIVE_RECORD = '0';

    public const DETAIL_RECORD = '1';

    public const FILE_TOTAL_RECORD = '7';

    protected string $abaFileContent = '';

    protected int $totalTransactions = 0;

    protected float $totalCreditAmount = 0;

    protected float $totalDebitAmount = 0;

    protected string $descriptiveString = '';

    protected string $detailString = '';

    protected string $fileTotalString = '';

    public function addDescriptiveRecord(array $record): string
    {
        PhpAbaValidator::validateDescriptiveRecord($record);

        // Position 1
        // Record Type
        $this->descriptiveString = self::DESCRIPTIVE_RECORD;

        // Position 2-18 - Blank spaces
        $this->descriptiveString .= $this->addBlankSpaces(17);

        // Postition 19 - 20
        // Reel Sequence Number
        $this->descriptiveString .= '01';

        // Position 21 - 23
        // Bank Name
        $this->descriptiveString .= $record['bank_name'];

        // Position 24 - 30 - Blank spaces
        $this->descriptiveString .= $this->addBlankSpaces(7);

        // Position 31 - 56
        // User Name
        $this->descriptiveString .= $this->padString($record['user_name'], '26');

        // Postion 57 - 62
        // User Number (as allocated by APCA)
        $this->descriptiveString .= $this->padString($record['user_number'], '6', '0', STR_PAD_RIGHT);

        // Position 63 - 74
        // Description
        $this->descriptiveString .= $this->padString($record['description'], '12');

        // Position 75 - 80
        // Processing date - Format (DDMMYY)
        $this->descriptiveString .= $record['process_date'];

        // Position 81-120 - Blank spaces
        $this->descriptiveString .= $this->addBlankSpaces(40);

        $this->descriptiveString .= $this->addLineBreak();

        return $this->descriptiveString;
    }

    public function addDetailRecord(array $transaction)
    {
        $transaction['indicator'] = $transaction['indicator'] ?? ' ';
        $transaction['withholding_tax'] = $transaction['withholding_tax'] ?? 0;

        // handle validating the structure and transaction code
        PhpAbaValidator::validateDetailRecord($transaction);
        PhpAbaValidator::validateTransactionCode($transaction['transaction_code']);

        // Calculate debit or credit amount
        $this->calculateDebitOrCreditAmount($transaction);

        // Increment total transactions
        $this->totalTransactions++;

        // Generate detail record string for a transaction
        // Record Type
        // Position 1
        $this->detailString .= self::DETAIL_RECORD;

        // BSB
        // Position 2-8
        // If there is no dash, add it between the first 3 and last 3 digits
        $this->detailString .= $this->formatBsb($transaction['bsb']);

        // Account Number
        // Position 9-17
        $this->detailString .= $this->padString($transaction['account_number'], '9', ' ', STR_PAD_LEFT);

        // Indicator
        // Position 18
        $this->detailString .= $transaction['indicator'];

        // Transaction Code
        // Position 19-20
        $this->detailString .= $transaction['transaction_code'];

        // Transaction Amount
        // Position 21-30
        $this->detailString .= $this->padString($this->dollarsToCents($transaction['amount']), '10', '0', STR_PAD_LEFT);

        // Account Name
        // Position 31-62
        $this->detailString .= $this->padString($transaction['account_name'], '32');

        // Lodgement Reference
        // Position 63-80
        $this->detailString .= $this->padString($transaction['reference'], '18');

        // Trace BSB
        // Bank (FI)/State/Branch and account number of User to enable retracing of the entry to its source if necessary
        // Position 81-87
        $this->detailString .= $this->formatBsb($transaction['trace_bsb']);

        // Trace Account Number
        // Position 88-96
        $this->detailString .= $this->padString($transaction['trace_account_number'], '9', ' ', STR_PAD_LEFT);

        // Remitter Name
        // Position 97-112
        $this->detailString .= $transaction['remitter'] ? $this->padString($transaction['remitter'], '16') : $this->addBlankSpaces(16);

        // Withholding amount
        // Position 113-120
        $this->detailString .= $this->padString($this->dollarsToCents($transaction['withholding_tax']), '8', '0', STR_PAD_LEFT);

        $this->detailString .= $this->addLineBreak();

        return $this->detailString;
    }

    public function addTransaction(array $transaction): string
    {
        return $this->addDetailRecord($transaction);
    }

    public function addTransactions(array $transactions): self
    {
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction);
        }

        return $this;
    }

    /**
     * Generate file total string
     *
     * @return string
     */
    public function addFileTotalRecord()
    {
        // Record Type - Must be 7
        // Position 1
        $this->fileTotalString = self::FILE_TOTAL_RECORD;

        // Must be '999-999'
        // Position 2-8
        $this->fileTotalString .= '999-999';

        // Must be blank
        // Position 9-20
        $this->fileTotalString .= $this->addBlankSpaces(12);

        // File net total amount
        // Position 21-30
        $this->fileTotalString .= $this->padString($this->dollarsToCents($this->getNetTotal()), '10', '0', STR_PAD_LEFT);

        // File credit total amount
        // Position 31-40
        $this->fileTotalString .= $this->padString($this->dollarsToCents($this->totalCreditAmount), '10', '0', STR_PAD_LEFT);

        // File debit total amount
        // Position 41-50
        $this->fileTotalString .= $this->padString($this->dollarsToCents($this->totalDebitAmount), '10', '0', STR_PAD_LEFT);

        // Must be 24 blank spaces
        // Position 51-74
        $this->fileTotalString .= $this->addBlankSpaces(24);

        // Number of records
        // Position 75-80
        $this->fileTotalString .= $this->padString($this->totalTransactions, '6', '0', STR_PAD_LEFT);

        // Must be 40 blank spaces
        // Position 81-120
        $this->fileTotalString .= $this->addBlankSpaces(40);

        $this->fileTotalString .= $this->addLineBreak();

        return $this->fileTotalString;
    }

    protected function formatBsb($bsb)
    {
        if (! empty($bsb) && strpos($bsb, '-') === false) {
            return substr($bsb, 0, 3).'-'.substr($bsb, 3, 3);
        }

        return $bsb;
    }

    /**
     * Generate ABA file content
     *
     * @return string
     */
    public function generate()
    {
        $this->addFileTotalRecord();

        $this->abaFileContent = $this->descriptiveString.$this->detailString.$this->fileTotalString;

        return $this->abaFileContent;
    }

    public function addBlankSpaces($number)
    {
        return str_repeat(' ', $number);
    }

    public function padString($value, $length, $padString = ' ', $type = STR_PAD_RIGHT)
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return str_pad(substr($value, 0, $length), $length, $padString, $type);
    }

    public function dollarsToCents($amount)
    {
        return $amount * 100;
    }

    public function getTotalCreditAmount()
    {
        return $this->totalCreditAmount;
    }

    public function getTotalDebitAmount()
    {
        return $this->totalDebitAmount;
    }

    protected function calculateDebitOrCreditAmount(array $transaction)
    {
        if ($transaction['transaction_code'] == PhpAbaValidator::$transactionCodes['externally_initiated_debit']) {
            $this->totalDebitAmount += $transaction['amount'];
        } else {
            $this->totalCreditAmount += $transaction['amount'];
        }
    }

    public function getNetTotal()
    {
        return abs($this->totalCreditAmount - $this->totalDebitAmount);
    }

    public function addLineBreak()
    {
        return "\r\n";
    }
}
