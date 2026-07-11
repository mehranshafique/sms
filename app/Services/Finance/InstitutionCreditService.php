<?php

namespace App\Services\Finance;

use App\Models\Institution;
use App\Models\InstitutionCreditTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstitutionCreditService
{
    public function recharge(int $institutionId, string $type, int $amount, ?string $note = null): InstitutionCreditTransaction
    {
        $this->assertType($type);
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($institutionId, $type, $amount, $note) {
            $institution = Institution::lockForUpdate()->findOrFail($institutionId);
            $column = $this->creditColumn($type);
            $before = (int) $institution->{$column};
            $after = $before + $amount;

            $institution->{$column} = $after;
            $institution->save();

            return InstitutionCreditTransaction::create([
                'institution_id' => $institutionId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'action' => 'recharge',
                'note' => $note,
                'performed_by' => Auth::id(),
            ]);
        });
    }

    public function reverse(InstitutionCreditTransaction $transaction, ?string $reason = null): InstitutionCreditTransaction
    {
        if ($transaction->status !== 'active') {
            throw ValidationException::withMessages([
                'transaction' => __('configuration.recharge_already_reversed'),
            ]);
        }

        if ($transaction->amount <= 0) {
            throw ValidationException::withMessages([
                'transaction' => __('configuration.recharge_cannot_reverse_negative'),
            ]);
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $institution = Institution::lockForUpdate()->findOrFail($transaction->institution_id);
            $column = $this->creditColumn($transaction->type);
            $before = (int) $institution->{$column};
            $after = max(0, $before - (int) $transaction->amount);

            $institution->{$column} = $after;
            $institution->save();

            $transaction->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => Auth::id(),
                'reversal_reason' => $reason,
            ]);

            return InstitutionCreditTransaction::create([
                'institution_id' => $transaction->institution_id,
                'type' => $transaction->type,
                'amount' => -1 * (int) $transaction->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'action' => 'reversal',
                'note' => $reason,
                'performed_by' => Auth::id(),
                'reverses_transaction_id' => $transaction->id,
            ]);
        });
    }

    /**
     * Reverse a mistaken recharge and apply the correct amount in one step.
     */
    public function correct(
        InstitutionCreditTransaction $transaction,
        int $correctAmount,
        ?string $reason = null
    ): array {
        $this->assertPositiveAmount($correctAmount);

        return DB::transaction(function () use ($transaction, $correctAmount, $reason) {
            $reversal = $this->reverse($transaction, $reason ?: __('configuration.recharge_corrected'));
            $replacement = $this->recharge(
                $transaction->institution_id,
                $transaction->type,
                $correctAmount,
                __('configuration.recharge_correction_replacement', ['id' => $transaction->id])
            );

            return ['reversal' => $reversal, 'replacement' => $replacement];
        });
    }

    private function creditColumn(string $type): string
    {
        $this->assertType($type);

        return $type === 'whatsapp' ? 'whatsapp_credits' : 'sms_credits';
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['sms', 'whatsapp'], true)) {
            throw ValidationException::withMessages(['type' => __('configuration.invalid_credit_type')]);
        }
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => __('configuration.enter_amount')]);
        }
    }

    /**
     * Align institution balances with the latest ledger entry per channel.
     * Fixes rows created before whatsapp_credits was mass-assignable.
     */
    public function syncBalancesFromLedger(int $institutionId): void
    {
        $institution = Institution::find($institutionId);
        if (! $institution) {
            return;
        }

        foreach (['sms', 'whatsapp'] as $type) {
            $column = $this->creditColumn($type);
            $latest = InstitutionCreditTransaction::query()
                ->where('institution_id', $institutionId)
                ->where('type', $type)
                ->latest('id')
                ->first();

            if (! $latest) {
                continue;
            }

            $ledgerBalance = (int) $latest->balance_after;
            if ((int) $institution->{$column} !== $ledgerBalance) {
                $institution->{$column} = $ledgerBalance;
            }
        }

        $institution->save();
    }
}
