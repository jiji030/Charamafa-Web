<?php

namespace App\Services;

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

class PrinterService
{
    public function printReceipt($data)
    {
        try {
            // For network printer
            $connector = new NetworkPrintConnector("192.168.1.87", 9100);
            $printer = new Printer($connector);

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT);
            $printer->text("CHARMAFA\n");
            $printer->selectPrintMode();
            $printer->text("Water Association\n");
            $printer->text("Payment Receipt\n\n");            
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Date: " . date('Y-m-d H:i:s') . "\n");
            $printer->text("OR #: " . ($data['or_number'] ?? $data['receipt_number'] ?? 'N/A') . "\n\n");

            $printer->setEmphasis(true);
            $printer->text("Account Details:\n");
            $printer->setEmphasis(false);
            $printer->text("Name: " . $data['member_name'] . "\n");
            $printer->text("Acc #: " . $data['account_no'] . "\n");
            $printer->text("Meter #: " . $data['meter_no'] . "\n\n");

            $printer->setEmphasis(true);
            $printer->text("Payment Details:\n");
            $printer->setEmphasis(false);
            $printer->text("Total Bill: ₱" . number_format($data['total_bill'], 2) . "\n");
            $printer->text("Cash: ₱" . number_format($data['cash'], 2) . "\n");
            $printer->text("Change: ₱" . number_format($data['change'], 2) . "\n\n");

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Thank you for your payment!\n");
            $printer->text("Processed by: " . $data['processor_name'] . "\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Printing failed: ' . $e->getMessage());
        }
    }
}
