<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Models\OrganizationMember;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MessageService;
use App\Services\NotificationDispatcher;
use App\Support\CurrentContext;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function messages(CurrentContext $context)
    {
        $threads = MessageThread::query()
            ->whereHas('participants', fn ($query) => $query->where('users.id', $context->member()->user_id))
            ->with(['participants', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->latest('updated_at')
            ->get();
        $tenants = Tenant::query()->whereNotNull('user_id')->orderBy('name')->get();
        $members = OrganizationMember::query()->with('user')->where('status', 'active')->where('user_id', '!=', $context->member()->user_id)->get();

        return view('office.messages.index', compact('threads', 'tenants', 'members'));
    }

    public function storeMessage(Request $request, CurrentContext $context, MessageService $messages)
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        $recipient = User::query()->findOrFail($data['recipient_id']);
        $message = $messages->send(
            $context->organization(),
            $request->user(),
            $recipient,
            $data['body'],
            store_upload($request->file('attachment'), 'messages'),
        );

        return redirect()->route('office.messages.show', $message->message_thread_id)->with('status', 'Message envoyé.');
    }

    public function showThread(MessageThread $thread, CurrentContext $context, MessageService $messages)
    {
        $this->authorize('view', $thread);
        $thread->load(['messages.sender', 'participants']);
        $messages->markRead($thread, request()->user());

        return view('office.messages.show', ['thread' => $thread]);
    }

    public function reply(Request $request, MessageThread $thread, CurrentContext $context, MessageService $messages)
    {
        $this->authorize('view', $thread);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        $thread->load('participants');
        $recipient = $thread->participants->first(fn ($user) => $user->id !== $request->user()->id);
        abort_if($recipient === null, 404);
        $messages->send($context->organization(), $request->user(), $recipient, $data['body'], store_upload($request->file('attachment'), 'messages'));

        return back()->with('status', 'Message envoyé.');
    }

    public function notifications(Request $request)
    {
        $items = $request->user()->notifications()->latest()->paginate(30);

        return view('office.notifications.index', [
            'items' => $items,
            'tenants' => Tenant::query()->whereNotNull('user_id')->orderBy('name')->get(),
            'properties' => Property::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function readNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Notifications lues.');
    }

    public function broadcast(Request $request, CurrentContext $context, NotificationDispatcher $notifications)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:one,several,all,property,unit'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'tenant_id' => ['nullable', 'integer'],
            'tenant_ids' => ['nullable', 'array'],
            'tenant_ids.*' => ['integer'],
            'property_id' => ['nullable', 'integer'],
            'unit_id' => ['nullable', 'integer'],
        ]);

        $query = Tenant::query()->whereNotNull('user_id')->with('user');

        $query = match ($data['audience']) {
            'one' => $query->whereKey($data['tenant_id'] ?? 0),
            'several' => $query->whereIn('id', $data['tenant_ids'] ?? []),
            'property' => $query->whereHas('contracts', fn ($contracts) => $contracts->where('property_id', $data['property_id'] ?? 0)->whereIn('status', ['active', 'move_out_requested'])),
            'unit' => $query->whereHas('contracts', fn ($contracts) => $contracts->where('unit_id', $data['unit_id'] ?? 0)->whereIn('status', ['active', 'move_out_requested'])),
            default => $query,
        };

        $users = $query->get()->pluck('user')->filter();
        $notifications->notifyUsers($users, 'broadcast', $data['title'], $data['body'], route('portal.notifications.index'));
        app(AuditLogger::class)->log($context->organization()->id, $request->user(), 'notification.sent', null, 'A envoyé « '.$data['title'].' » à '.$users->count().' locataire(s).');

        return back()->with('status', 'Notification envoyée.');
    }
}
