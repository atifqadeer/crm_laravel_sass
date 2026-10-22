<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * Upload and soft-delete attachments for head offices and units.
 * Files live under public/<directory>, the same way sale documents do.
 */
trait HandlesModuleDocuments
{
    /** Allowed attachment types; max size is 10 MB per file. */
    protected function documentExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png'];
    }

    protected function documentValidationRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:10'],
            // nullable: an empty file input still posts one blank attachments[] entry.
            'attachments.*' => ['nullable', 'file', 'extensions:' . implode(',', $this->documentExtensions()), 'max:10240'],
        ];
    }

    /**
     * Move the uploaded files into public/$directory and create a document row for each.
     * Returns the stored relative paths so the caller can delete them if its transaction fails.
     *
     * @param  UploadedFile[]  $files
     * @param  class-string<Model>  $documentModel
     */
    protected function storeModuleDocuments(array $files, string $documentModel, string $ownerKey, int $ownerId, string $directory): array
    {
        $stored = [];
        $publicDir = public_path($directory);

        if ($files && !is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $size = $file->getSize();
            $storedName = $this->documentFileName($publicDir, $originalName, $extension);

            $file->move($publicDir, $storedName);
            $path = $directory . '/' . $storedName;
            $stored[] = $path;

            $documentModel::create([
                $ownerKey => $ownerId,
                'user_id' => Auth::id(),
                'document_name' => mb_substr($originalName, 0, 255),
                'document_path' => $path,
                'document_size' => $size,
                'document_extension' => $extension,
            ]);
        }

        return $stored;
    }

    /**
     * Keep the original file name and append the upload date and time,
     * e.g. "Contract Final.pdf" → "Contract Final_22-10-2026_14-35-08.pdf".
     * Only characters that are invalid in file names are replaced, and a
     * counter is added if that exact name already exists.
     */
    private function documentFileName(string $directory, string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F]+/u', '_', $baseName);
        $baseName = trim($baseName, " ._") ?: 'document';
        $baseName = mb_substr($baseName, 0, 150);

        $stamped = $baseName . '_' . now()->format('d-m-Y_H-i-s');
        $name = $stamped . '.' . $extension;

        for ($i = 2; is_file($directory . DIRECTORY_SEPARATOR . $name); $i++) {
            $name = $stamped . '_' . $i . '.' . $extension;
        }

        return $name;
    }

    /** JSON rows for the "Show Documents" modal on the list pages. */
    protected function moduleDocumentsPayload($documents): array
    {
        return $documents->map(fn ($document) => [
            'id' => $document->id,
            'name' => $document->document_name,
            'url' => $document->url,
            'extension' => strtoupper((string) $document->document_extension),
            'size' => $document->formatted_size,
            'uploaded_at' => $document->created_at?->format('d M Y, h:i A'),
            'uploaded_by' => $document->user?->name,
        ])->values()->all();
    }

    /** Remove files written by storeModuleDocuments() when the surrounding transaction failed. */
    protected function discardStoredDocuments(array $paths): void
    {
        foreach ($paths as $path) {
            $fullPath = public_path($path);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    /**
     * Soft delete a document with the reason. The file stays on disk until
     * documents:purge-deleted removes it three days later.
     */
    protected function softDeleteModuleDocument(Model $document, string $reason): void
    {
        $document->update([
            'delete_reason' => $reason,
            'deleted_by' => Auth::id(),
        ]);

        $document->delete();
    }
}
