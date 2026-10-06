<?php

declare(strict_types = 1);

final class ExpressionLayouts
{
    public function authorize(int $actorUserId): void
    {
        abort_unless(
            boolean: array_diff([
                CrmPermissionRepository::ACCESS,
                'crm.settings.manage',
            ], $this->crmPermissionRepository->forUser($actorUserId)) === [],
            code: 403,
            message: 'You do not have permission to manage CRM security policies.',
        );
    }

    public function profile(object $lead): ProfileData
    {
        return new ProfileData(
            name: trim(
                sprintf('%s %s', $lead->first_name, $lead->last_name),
            ) ?: 'Unnamed lead',
        );
    }

    public function notify(object $notification, object $aaa): void
    {
        $notification
            ->body('Google revocation failed. Remove this app from your Google account permissions.')
            ->warning()
            ->send();

        $aaa
            ->body('Google revocation failed. Remove this app from your Google account permissions.')
            ->warning()
            ->send();
    }
}
