<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COLUMN = 'comment';

    public const MIRROR_COLUMN = 'approval_status';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::STATUS_PENDING => __('aho.approval.pending'),
            self::STATUS_APPROVED => __('aho.approval.approved'),
            self::STATUS_REJECTED => __('aho.approval.rejected'),
        ];
    }

    public static function label(?string $status): string
    {
        return static::options()[static::normalizeStatus($status)] ?? __('aho.approval.pending');
    }

    public static function color(?string $status): string
    {
        return match (static::normalizeStatus($status)) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    public static function isApproved(Model $record): bool
    {
        return static::status($record) === self::STATUS_APPROVED;
    }

    public static function status(Model $record): string
    {
        $legacyStatus = $record->getAttribute(self::STATUS_COLUMN);

        if (static::isKnownStatus($legacyStatus)) {
            return (string) $legacyStatus;
        }

        return static::normalizeStatus($record->getAttribute(self::MIRROR_COLUMN));
    }

    public static function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return static::isKnownStatus($status) ? $status : self::STATUS_PENDING;
    }

    public static function syncStatus(Model $record, ?string $status = null): void
    {
        $status = static::normalizeStatus($status ?? static::status($record));

        $record->setAttribute(self::STATUS_COLUMN, $status);
        $record->setAttribute(self::MIRROR_COLUMN, $status);

        if ($status !== self::STATUS_APPROVED) {
            $record->setAttribute('approved_by', null);
            $record->setAttribute('approved_at', null);

            return;
        }

        $record->setAttribute('approved_by', $record->getAttribute('approved_by') ?? auth()->id());
        $record->setAttribute('approved_at', $record->getAttribute('approved_at') ?? now());
    }

    public static function whereApproved(Builder $query): Builder
    {
        return $query->where(self::STATUS_COLUMN, self::STATUS_APPROVED);
    }

    public static function approve(Model $record): void
    {
        $record->forceFill([
            self::STATUS_COLUMN => self::STATUS_APPROVED,
            self::MIRROR_COLUMN => self::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ])->save();
    }

    public static function markPending(Model $record): void
    {
        $record->forceFill([
            self::STATUS_COLUMN => self::STATUS_PENDING,
            self::MIRROR_COLUMN => self::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    private static function isKnownStatus(mixed $status): bool
    {
        return in_array(strtolower(trim((string) $status)), [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ], true);
    }
}
