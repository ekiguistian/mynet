<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch_access', function (Builder $query): void {
            $user = Auth::user();

            if (! $user instanceof User || ! $user->isBranchScoped()) {
                return;
            }

            $branchPath = static::branchScopePath();

            if (! str_contains($branchPath, '.')) {
                $qualifiedBranchColumn = $query->getModel()->qualifyColumn($branchPath);

                $query->where(function (Builder $branchQuery) use ($qualifiedBranchColumn, $user): void {
                    $branchQuery->where($qualifiedBranchColumn, $user->branch_id);

                    if (static::includeSharedBranchRecords()) {
                        $branchQuery->orWhereNull($qualifiedBranchColumn);
                    }
                });

                return;
            }

            $pathSegments = explode('.', $branchPath);
            $branchColumn = array_pop($pathSegments);
            $relationshipPath = implode('.', $pathSegments);

            $query->whereHas($relationshipPath, fn (Builder $relatedQuery): Builder => $relatedQuery->where($branchColumn, $user->branch_id));
        });

        static::saving(function (Model $model): void {
            $user = Auth::user();

            if (
                $user instanceof User
                && $user->isBranchScoped()
            ) {
                if (static::branchScopePath() === 'branch_id') {
                    $model->setAttribute('branch_id', $user->branch_id);

                    return;
                }

                $pathSegments = explode('.', static::branchScopePath());
                $branchColumn = array_pop($pathSegments);
                $relatedModel = $model;

                foreach ($pathSegments as $relationship) {
                    $relatedModel = $relatedModel->{$relationship};

                    if (! $relatedModel instanceof Model) {
                        throw ValidationException::withMessages(['branch_id' => 'Relasi data tidak ditemukan atau berada di luar cabang Anda.']);
                    }
                }

                if ((int) $relatedModel->getAttribute($branchColumn) !== (int) $user->branch_id) {
                    throw ValidationException::withMessages(['branch_id' => 'Data hanya dapat diubah di cabang Anda.']);
                }
            }
        });
    }

    public function belongsToBranch(int $branchId): bool
    {
        $pathSegments = explode('.', static::branchScopePath());
        $branchColumn = array_pop($pathSegments);
        $relatedModel = $this;

        foreach ($pathSegments as $relationship) {
            if (! method_exists($relatedModel, $relationship)) {
                return false;
            }

            $relatedModel = $relatedModel->{$relationship};

            if (! $relatedModel instanceof Model) {
                return false;
            }
        }

        $recordBranchId = $relatedModel->getAttribute($branchColumn);

        return $recordBranchId === null
            ? static::includeSharedBranchRecords()
            : (int) $recordBranchId === $branchId;
    }

    protected static function branchScopePath(): string
    {
        return 'branch_id';
    }

    protected static function includeSharedBranchRecords(): bool
    {
        return false;
    }
}
