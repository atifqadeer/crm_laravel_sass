<?php

namespace App\Support;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI agent call statuses: one place for the list, the badges, the list-page
 * filters and the "may the agent call again?" rules.
 *
 * Table: ai_agent_calls (one row per call, see the 2026_11_20_120000 migration).
 * "status" is the outcome when the call was answered, otherwise the call_status.
 */
class AiCallStatus
{
    /** call_status values the agent may send. */
    public const CALL_STATUSES = ['answered', 'no_answer', 'busy', 'voicemail', 'wrong_number', 'failed'];

    /** outcome values (required when call_status = answered). */
    public const OUTCOMES = ['interested', 'not_interested', 'callback', 'not_looking', 'do_not_call'];

    /** status => [label, badge classes], in the order the filters show them. */
    public const STATUSES = [
        'interested' => ['Interested', 'bg-success'],
        'callback' => ['Callback', 'bg-info'],
        'not_interested' => ['Not Interested', 'bg-danger'],
        'not_looking' => ['Not Looking', 'bg-secondary'],
        'no_answer' => ['No Answer', 'bg-warning text-dark'],
        'busy' => ['Busy', 'bg-warning text-dark'],
        'voicemail' => ['Voicemail', 'bg-warning text-dark'],
        'failed' => ['Call Failed', 'bg-light text-dark border'],
        'wrong_number' => ['Wrong Number', 'bg-dark'],
        'do_not_call' => ['Do Not Call', 'bg-dark'],
    ];

    /** Filter value for applicants the agent has never called. */
    public const NOT_CALLED = 'not_called';

    // ── Re-call rules for API 1 (job matches) ─────────────────────────────
    /** Never sent again, for any job. */
    public const BLOCKING = ['do_not_call', 'wrong_number'];
    /** Final answer for that job: not sent again for the same job. */
    public const FINAL_FOR_JOB = ['interested', 'not_interested', 'not_looking'];
    /** Line problems: retried for the same job after a pause, a few times. */
    public const RETRYABLE = ['no_answer', 'busy', 'voicemail', 'failed'];
    public const RETRY_AFTER_HOURS = 24;
    public const MAX_RETRY_ATTEMPTS = 3;

    private static ?bool $tableExists = null;

    /** False until the migration has run, so pages keep working before it. */
    public static function enabled(): bool
    {
        return self::$tableExists ??= Schema::hasTable('ai_agent_calls');
    }

    public static function statusFor(string $callStatus, ?string $outcome): string
    {
        return $callStatus === 'answered' ? (string) $outcome : $callStatus;
    }

    public static function label(?string $status): string
    {
        return self::STATUSES[$status][0] ?? ucwords(str_replace('_', ' ', (string) $status));
    }

    /** Filter options for the list pages: status => label, plus "Not called". */
    public static function filterOptions(): array
    {
        $options = [self::NOT_CALLED => 'Not Called Yet'];
        foreach (self::STATUSES as $key => [$label]) {
            $options[$key] = $label;
        }

        return $options;
    }

    /** Keep only known filter values from a request. */
    public static function cleanFilter($value): array
    {
        $allowed = array_keys(self::filterOptions());

        return array_values(array_intersect($allowed, array_map('strval', (array) $value)));
    }

    /**
     * Badge for a list cell. Clicking it opens the call history modal
     * (resources/views/partials/ai-call-history.blade.php).
     */
    public static function badge(?string $status, $calledAt = null, ?string $context = null, int $applicantId = 0, ?int $saleId = null, $callbackAt = null): string
    {
        if (!$status) {
            return '<span class="text-muted small">Not called</span>';
        }

        [$label, $classes] = self::STATUSES[$status] ?? [self::label($status), 'bg-light text-dark border'];
        $when = $calledAt ? Carbon::parse($calledAt)->format('d M Y H:i') : '';
        $callback = ($status === 'callback' && $callbackAt)
            ? '<div class="small text-info">Call back ' . e(Carbon::parse($callbackAt)->format('d M H:i')) . '</div>'
            : '';

        return '<a href="#" class="ai-status-badge text-decoration-none" data-applicant-id="' . $applicantId . '"'
            . ($saleId ? ' data-sale-id="' . $saleId . '"' : '')
            . ' title="AI agent call history">'
            . '<span class="badge ' . $classes . '"><i class="ri-robot-2-line me-1"></i>' . e($label) . '</span></a>'
            . $callback
            . ($when ? '<div class="small text-muted">' . e($when) . '</div>' : '')
            . ($context ? '<div class="small text-muted">' . e($context) . '</div>' : '');
    }

    /**
     * Filter applicants by their latest AI status. With $saleId: the latest
     * call for that sale only; without: the latest call for any sale.
     *
     * @param \Illuminate\Database\Eloquent\Builder|QueryBuilder $query
     */
    public static function applyFilter($query, array $statuses, ?int $saleId = null): void
    {
        if (!$statuses || !self::enabled()) {
            return;
        }

        $wantNotCalled = in_array(self::NOT_CALLED, $statuses, true);
        $statuses = array_values(array_diff($statuses, [self::NOT_CALLED]));

        $query->where(function ($q) use ($statuses, $wantNotCalled, $saleId) {
            if ($statuses) {
                $latestIds = DB::table('ai_agent_calls')
                    ->selectRaw('MAX(id)')
                    ->when($saleId, fn($s) => $s->where('sale_id', $saleId))
                    ->groupBy('applicant_id');

                $q->orWhereIn('applicants.id', DB::table('ai_agent_calls')
                    ->select('applicant_id')
                    ->whereIn('id', $latestIds)
                    ->whereIn('status', $statuses));
            }

            if ($wantNotCalled) {
                $q->orWhereNotExists(function ($s) use ($saleId) {
                    $s->selectRaw('1')
                        ->from('ai_agent_calls')
                        ->whereColumn('ai_agent_calls.applicant_id', 'applicants.id')
                        ->when($saleId, fn($w) => $w->where('ai_agent_calls.sale_id', $saleId));
                });
            }
        });
    }

    /**
     * Re-call rules for API 1, on a query that already joins `applicants` and `sales`:
     *  - never again (any job) after do_not_call / wrong_number;
     *  - not again for the same job after interested / not_interested / not_looking;
     *  - callback: again once callback_at has passed;
     *  - no_answer / busy / voicemail / failed: again after RETRY_AFTER_HOURS,
     *    up to MAX_RETRY_ATTEMPTS calls for that job.
     */
    public static function applyRecallRules(QueryBuilder $query, array $saleIds): void
    {
        if (!self::enabled()) {
            return;
        }

        $now = now();
        $retryBefore = $now->copy()->subHours(self::RETRY_AFTER_HOURS);
        $retryList = "'" . implode("','", self::RETRYABLE) . "'";

        $pairs = DB::table('ai_agent_calls')
            ->whereIntegerInRaw('sale_id', $saleIds)
            ->groupBy('applicant_id', 'sale_id')
            ->selectRaw("applicant_id, sale_id, MAX(id) as last_id, SUM(status IN ({$retryList})) as retry_attempts");

        $query
            ->leftJoinSub($pairs, 'ai_pair', function ($join) {
                $join->on('ai_pair.applicant_id', '=', 'applicants.id')
                    ->on('ai_pair.sale_id', '=', 'sales.id');
            })
            ->leftJoin('ai_agent_calls as ai_last', 'ai_last.id', '=', 'ai_pair.last_id')
            ->where(function ($q) use ($now, $retryBefore) {
                $q->whereNull('ai_pair.last_id')
                    ->orWhere(function ($c) use ($now) {
                        $c->where('ai_last.status', 'callback')
                            ->where(fn($w) => $w->whereNull('ai_last.callback_at')->orWhere('ai_last.callback_at', '<=', $now));
                    })
                    ->orWhere(function ($r) use ($retryBefore) {
                        $r->whereIn('ai_last.status', self::RETRYABLE)
                            ->where('ai_last.called_at', '<=', $retryBefore)
                            ->where('ai_pair.retry_attempts', '<', self::MAX_RETRY_ATTEMPTS);
                    });
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('ai_agent_calls as ai_block')
                    ->whereColumn('ai_block.applicant_id', 'applicants.id')
                    ->whereIn('ai_block.status', self::BLOCKING);
            });
    }
}
