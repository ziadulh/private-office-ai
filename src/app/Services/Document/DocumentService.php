<?php

namespace App\Services\Document;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(private AuditService $auditService) {}

    public function accessibleDocumentsQuery(User $user): Builder
    {
        $query = Document::with(['department', 'category', 'uploader']);

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $roleIds = $user->roles->pluck('id')->toArray();

        return $query->where(function (Builder $q) use ($user, $roleIds) {
            $q->where('uploaded_by', $user->id)
              ->orWhere('confidentiality', 'public_internal')
              ->orWhere(function (Builder $sub) use ($user) {
                  $sub->where('confidentiality', 'department_restricted')
                      ->whereNotNull('department_id')
                      ->where('department_id', $user->department_id);
              })
              ->orWhereHas('permissions', function (Builder $perm) use ($user, $roleIds) {
                  $perm->where('can_view', true)
                       ->where(function (Builder $target) use ($user, $roleIds) {
                           $target->where('user_id', $user->id)
                                  ->orWhereIn('role_id', $roleIds)
                                  ->when($user->department_id, fn ($d) => $d->orWhere('department_id', $user->department_id));
                       });
              });

            if ($user->hasPermission('documents.confidential')) {
                $q->orWhere(function (Builder $sub) use ($user) {
                    $sub->where('confidentiality', 'confidential');
                    if (!$user->hasRole('admin')) {
                        $sub->where(function ($d) use ($user) {
                            $d->whereNull('department_id')->orWhere('department_id', $user->department_id);
                        });
                    }
                });
            }
        });
    }

    public function upload(UploadedFile $file, array $data, User $uploader): array
    {
        return DB::transaction(function () use ($file, $data, $uploader) {
            $checksum = hash_file('sha256', $file->getRealPath());
            $duplicateExists = Document::where('checksum', $checksum)->exists();

            $uuid = (string) Str::uuid();
            $extension = strtolower($file->getClientOriginalExtension());
            $storedFilename = $uuid . '.' . $extension;
            $directory = 'private/documents/' . now()->format('Y/m');
            $storagePath = $file->storeAs($directory, $storedFilename, 'local');

            $document = Document::create([
                'uuid' => $uuid,
                'title' => $data['title'],
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'storage_path' => $storagePath,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'file_size' => $file->getSize(),
                'checksum' => $checksum,
                'department_id' => $data['department_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'confidentiality' => $data['confidentiality'],
                'description' => $data['description'] ?? null,
                'status' => 'pending',
                'uploaded_by' => $uploader->id,
            ]);

            if (!empty($data['grant_user_ids'])) {
                foreach ($data['grant_user_ids'] as $targetUserId) {
                    $document->permissions()->create([
                        'user_id' => $targetUserId,
                        'can_view' => true,
                        'can_download' => true,
                    ]);
                }
            }

            $this->auditService->log('document.uploaded', $document, [
                'title' => $document->title,
                'confidentiality' => $document->confidentiality,
                'size' => $document->file_size,
            ]);

            ProcessDocumentJob::dispatch($document->id);

            return ['document' => $document, 'is_duplicate' => $duplicateExists];
        });
    }

    public function delete(Document $document): void
    {
        DB::transaction(function () use ($document) {
            $this->auditService->log('document.deleted', $document, [
                'title' => $document->title,
                'uuid' => $document->uuid,
            ]);

            if (Storage::disk('local')->exists($document->storage_path)) {
                Storage::disk('local')->delete($document->storage_path);
            }

            $document->delete();
        });
    }
}
