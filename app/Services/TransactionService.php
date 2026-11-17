<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ClassModel;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransactionService
{
    public function save(array $data, int $userId, ?UploadedFile $proof = null, ?Transaction $transaction = null, ?int $classId = null): Transaction
    {
        $actor = User::findOrFail($userId);
        abort_unless($actor->isBendahara(), 403);
        $actorClass = SchoolContext::requireForUser($actor);
        abort_if(($transaction && $transaction->class_id !== $actorClass->id) || ($classId && $classId !== $actorClass->id), 403);
        $classId = $actorClass->id;
        $newPath = null;
        $oldPath = $transaction?->proof_image;
        try {
            if ($proof) {
                $newPath = $proof->store('proofs', 'public');
                if (! $newPath) {
                    throw new \RuntimeException('Bukti transaksi gagal disimpan.');
                }
            }
            $transaction = DB::transaction(function () use ($data, $userId, $transaction, $newPath, &$oldPath, $classId) {
                $class = ClassModel::whereKey($classId)->lockForUpdate()->firstOrFail();
                if ($transaction) {
                    $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                    $oldPath = $transaction->proof_image;
                }
                $category = Category::firstOrCreate([
                    'class_id' => $class->id, 'name' => $data['category'], 'type' => $data['type'],
                ], ['color' => $data['type'] === 'income' ? '#10b981' : '#ef4444']);

                if (! $transaction) {
                    $duplicateQuery = Transaction::query()
                        ->where('class_id', $class->id)
                        ->where('user_id', $userId)
                        ->where('type', $data['type'])
                        ->where('amount', $data['amount'])
                        ->where('transaction_date', $data['date'])
                        ->where('description', trim((string) $data['description']));

                    if ($duplicateQuery->exists()) {
                        throw ValidationException::withMessages([
                            'description' => 'Transaksi dengan kunci data yang sama sudah ada. Silakan cek kembali sebelum menyimpan.',
                        ]);
                    }
                }

                $attributes = [
                    'class_id' => $class->id,
                    'category_id' => $category->id,
                    'type' => $data['type'], 'amount' => $data['amount'],
                    'title' => Str::limit($data['description'], 255, ''), 'description' => $data['description'],
                    'transaction_date' => $data['date'],
                    'proof_image' => $newPath ?: $transaction?->proof_image,
                ];
                if ($transaction) {
                    $transaction->update($attributes);
                } else {
                    $transaction = Transaction::create($attributes + ['user_id' => $userId]);
                }
                $this->updateBalance($class);

                return $transaction;
            });
        } catch (Throwable $e) {
            if ($newPath) {
                $this->removeProof($newPath);
            }
            throw $e;
        }
        if ($newPath && $oldPath) {
            $this->removeProof($oldPath);
        }

        return $transaction;
    }

    public function delete(Transaction $transaction): void
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->isBendahara(), 403);
        abort_unless($transaction->class_id === SchoolContext::requireForUser($actor)->id, 403);
        $proof = $transaction->proof_image;
        DB::transaction(function () use ($transaction, &$proof) {
            $class = ClassModel::whereKey($transaction->class_id)->lockForUpdate()->firstOrFail();
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $proof = $transaction->proof_image;
            $transaction->delete();
            $this->updateBalance($class);
        });
        if ($proof) {
            $this->removeProof($proof);
        }
    }

    private function updateBalance(ClassModel $class): void
    {
        $class->update(['current_balance' => $class->transactions()->ofType('income')->sum('amount') - $class->transactions()->ofType('expense')->sum('amount')]);
    }

    private function removeProof(string $path): void
    {
        try {
            if (! Storage::disk('public')->delete($path)) {
                report(new \RuntimeException('Gagal menghapus bukti transaksi lama.'));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
