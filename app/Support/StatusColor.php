<?php

namespace App\Support;

class StatusColor
{
    public static function for(?string $status): string
    {
        return match (self::normalize($status)) {
            ApprovalWorkflow::STATUS_APPROVED,
            'active',
            'accredited',
            'ready',
            'success' => 'success',

            ApprovalWorkflow::STATUS_REJECTED,
            'error',
            'failed',
            'inactive',
            'unacredited',
            'unaccredited' => 'danger',

            ApprovalWorkflow::STATUS_PENDING,
            'charterted',
            'chartered',
            'missing',
            'paused',
            'suspended',
            'warning' => 'warning',

            'closed' => 'gray',

            default => 'gray',
        };
    }

    private static function normalize(?string $status): string
    {
        return strtolower(trim((string) $status));
    }
}
