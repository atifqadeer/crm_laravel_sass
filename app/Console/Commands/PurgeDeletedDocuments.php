<?php

namespace App\Console\Commands;

use Horsefly\OfficeDocument;
use Horsefly\UnitDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgeDeletedDocuments extends Command
{
    protected $signature = 'documents:purge-deleted {--days=3 : Remove documents deleted at least this many days ago}';

    protected $description = 'Permanently remove soft-deleted office and unit documents (row and file) after the grace period';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        foreach ([OfficeDocument::class, UnitDocument::class] as $model) {
            $purged = 0;
            $failed = 0;

            $model::onlyTrashed()
                ->where('deleted_at', '<=', $cutoff)
                ->chunkById(100, function ($documents) use (&$purged, &$failed) {
                    foreach ($documents as $document) {
                        try {
                            $fullPath = public_path($document->document_path);
                            if (is_file($fullPath) && !@unlink($fullPath)) {
                                throw new \RuntimeException("Could not delete file {$fullPath}");
                            }

                            $document->forceDelete();
                            $purged++;
                        } catch (Throwable $e) {
                            // Keep the row so the next run retries it.
                            $failed++;
                            Log::error('[PurgeDeletedDocuments] Failed', [
                                'model' => get_class($document),
                                'id' => $document->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });

            $label = class_basename($model);
            $this->info("{$label}: purged {$purged}, failed {$failed}.");

            if ($purged || $failed) {
                Log::info('[PurgeDeletedDocuments] ' . $label, ['purged' => $purged, 'failed' => $failed]);
            }
        }

        return self::SUCCESS;
    }
}
