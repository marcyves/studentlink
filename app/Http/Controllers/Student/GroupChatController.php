<?php

namespace App\Http\Controllers\Student;

use App\Events\GroupMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMessage;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupChatController extends Controller
{
    public function index(): Response
    {
        $groups = auth()->user()
            ->groups()
            ->with(['project.course', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->get()
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'project' => $group->project->title,
                'course' => $group->project->course->title,
                'last_message' => $group->messages->first()?->body,
            ]);

        return Inertia::render('Student/Chat/Index', [
            'groups' => $groups,
        ]);
    }

    public function show(Group $group): Response
    {
        $this->authorizeGroupMember($group);

        $group->load('project');

        $messages = $group->messages()
            ->with('user')
            ->oldest()
            ->limit(100)
            ->get()
            ->map(fn (GroupMessage $message) => $this->formatMessage($message));

        return Inertia::render('Student/Chat/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'project' => $group->project->title,
            ],
            'messages' => $messages,
            'members' => $group->members()->pluck('name'),
        ]);
    }

    public function store(Request $request, Group $group): RedirectResponse
    {
        $this->authorizeGroupMember($group);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $group->messages()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        $this->broadcastMessage($message);

        return back();
    }

    private function broadcastMessage(GroupMessage $message): void
    {
        try {
            GroupMessageSent::dispatch($message);
        } catch (BroadcastException $e) {
            Log::warning('Chat broadcast failed — is Reverb running?', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function authorizeGroupMember(Group $group): void
    {
        if (! auth()->user()->groups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }
    }

    private function formatMessage(GroupMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
            ],
        ];
    }
}
