<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TransferBalance extends Model
{
    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'amount',
        'transfer_date',
        'reference_number',
        'remarks'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date'
    ];

    public function fromAccount()
    {
        return $this->belongsTo(AccountList::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(AccountList::class, 'to_account_id');
    }
}
