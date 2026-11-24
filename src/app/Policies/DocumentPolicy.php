<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin() || $document->uploaded_by === $user->id) {
            return true;
        }

        // Check explicit granular permission grants first
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $hasExplicitGrant = $document->permissions()
            ->where('can_view', true)
            ->where(function ($q) use ($user, $userRoleIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('role_id', $userRoleIds)
                  ->when($user->department_id, fn ($sub) => $sub->orWhere('department_id', $user->department_id));
            })
            ->exists();

        if ($hasExplicitGrant) {
            return true;
        }

        return match ($document->confidentiality) {
            'public_internal' => $user->hasPermission('documents.view'),
            'department_restricted' => $user->hasPermission('documents.view')
                && $document->department_id !== null
                && $document->department_id === $user->department_id,
            'confidential' => $user->hasPermission('documents.confidential')
                && ($document->department_id === null || $document->department_id === $user->department_id || $user->hasRole('admin')),
            'highly_confidential' => false,
            default => false,
        };
    }

    public function download(User $user, Document $document): bool
    {
        if (!$user->hasPermission('documents.download') && !$user->isSuperAdmin()) {
            return false;
        }

        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('documents.delete') && $document->uploaded_by === $user->id;
    }
}
