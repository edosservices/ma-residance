<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DomainException;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(private NotificationDispatcher $notifications) {}

    public function send(Organization $organization, User $sender, User $recipient, string $body, ?string $attachment = null): Message
    {
        if ($sender->id === $recipient->id) {
            throw new DomainException('Choisissez un autre destinataire.');
        }

        if (trim($body) === '' && $attachment === null) {
            throw new DomainException('Le message est vide.');
        }

        if (! $this->belongsToOrganization($organization, $recipient)) {
            throw new DomainException('Ce destinataire n\'appartient pas à votre organisation.');
        }

        return DB::transaction(function () use ($organization, $sender, $recipient, $body, $attachment) {
            $pair = collect([$sender->id, $recipient->id])->sort()->implode(':');
            $thread = MessageThread::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organization->id, 'pair_key' => $pair],
                ['subject' => 'Conversation'],
            );

            $thread->participants()->syncWithoutDetaching([
                $sender->id => ['last_read_at' => now()],
                $recipient->id => [],
            ]);

            $message = Message::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'message_thread_id' => $thread->id,
                'sender_id' => $sender->id,
                'body' => $body,
                'attachment_path' => $attachment,
            ]);
            $thread->touch();

            $isMember = OrganizationMember::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('user_id', $recipient->id)
                ->where('status', 'active')
                ->exists();

            $this->notifications->notify(
                $recipient,
                'message.received',
                'Nouveau message',
                $sender->name.' : '.str($body)->limit(120),
                $isMember ? route('office.messages.show', $thread) : route('portal.messages.show', $thread),
            );

            return $message;
        });
    }

    public function markRead(MessageThread $thread, User $user): void
    {
        $thread->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
    }

    private function belongsToOrganization(Organization $organization, User $recipient): bool
    {
        $member = OrganizationMember::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('user_id', $recipient->id)
            ->where('status', 'active')
            ->exists();

        if ($member) {
            return true;
        }

        return Tenant::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('user_id', $recipient->id)
            ->where('status', 'active')
            ->exists();
    }
}
