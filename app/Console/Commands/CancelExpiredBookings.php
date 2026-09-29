<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BorrowTransaction;

class CancelExpiredBookings extends Command
{
    protected $signature = 'bookings:cancel-expired';
    protected $description = 'Membatalkan otomatis booking keranjang yang melebihi batas 1 hari';

    public function handle()
    {
        $expiredTransactions = BorrowTransaction::with('details.bookCopy')
            ->where('status', 'booked')
            ->where('booking_expires_at', '<', now())
            ->get();

        foreach ($expiredTransactions as $trx) {
            foreach ($trx->details as $detail) {
                $detail->bookCopy->update(['status' => 'available']);
                $detail->update(['status' => 'returned']);
            }
            $trx->update(['status' => 'cancelled']);
        }

        $this->info('Sukses membatalkan ' . $expiredTransactions->count() . ' booking keranjang yang kadaluarsa.');
    }
}