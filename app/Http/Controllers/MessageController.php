<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageRead;
use App\Models\Owner;
use App\Models\Syndic;
use App\Models\Property;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * How many messages are loaded per conversation on first page load.
 * Older messages are fetched on demand as the user scrolls up
 * (see olderMessages()), the same way WhatsApp lazy-loads history.
 */

class MessageController extends Controller
{
    private const PAGE_SIZE = 30;

    public function index(Request $request)
    {
        $user = Auth::user();
        $contacts = collect();
        $conversations = collect();
        $activeOwnerId = $request->query('owner');

        if ($user->role === 'syndic') {
            $propertyId = $user->syndic->property_id ?? null;
            $owners = Owner::where('property_id', $propertyId)->with('user')->get();

            $conversations = $owners->map(function ($owner) use ($propertyId) {
                $messages = Message::where('property_id', $propertyId)
                    ->where(function ($query) use ($owner) {
                        $query->whereNull('owner_id')->orWhere('owner_id', $owner->id);
                    })
                    ->orderBy('created_at')
                    ->get();

                $unread = $messages->where('sender_type', 'owner')->where('is_read', false)->count();

                return (object) [
                    'owner_id' => $owner->id,
                    'owner' => $owner,
                    'messages' => $messages,
                    'last_message' => $messages->last(),
                    'unread_count' => $unread,
                ];
            })->sortByDesc(function ($c) {
                return optional($c->last_message)->created_at;
            })->values();

            $contacts = $owners;

            if (! $activeOwnerId) {
                $activeOwnerId = optional($conversations->first())->owner_id;
            }
        } else {
            $owner = $user->owner ?? null;
            if ($owner) {
                $messages = Message::where('property_id', $owner->property_id)
                    ->where(function ($query) use ($owner) {
                        $query->whereNull('owner_id')->orWhere('owner_id', $owner->id);
                    })
                    ->with(['reads' => function ($q) use ($owner) {
                        $q->where('owner_id', $owner->id);
                    }])
                    ->orderBy('created_at')
                    ->get();

                $unread = $messages->where('sender_type', 'syndic')
                    ->filter(fn ($m) => ! $m->isReadByOwner($owner->id))
                    ->count();

                $conversations = collect([(object) [
                    'owner_id' => $owner->id,
                    'owner' => $owner,
                    'messages' => $messages,
                    'last_message' => $messages->last(),
                    'unread_count' => $unread,
                ]]);

                $syndic = $owner->property?->syndics()->with('user')->first();
                $contacts = $syndic ? collect([$syndic]) : collect();
                $activeOwnerId = $owner->id;
            }
        }

        // Mark the currently opened conversation as read.
        if ($activeOwnerId) {
            $this->markThreadRead($user, (int) $activeOwnerId);
            $conversations = $conversations->map(function ($c) use ($activeOwnerId) {
                if ((int) $c->owner_id === (int) $activeOwnerId) {
                    $c->unread_count = 0;
                }
                return $c;
            });
        }

        // Only send the most recent page of the active conversation to the
        // browser. Older messages are fetched on scroll-up via olderMessages().
        $chatHasMore = false;
        $chatOldestId = null;
        if ($activeOwnerId) {
            $conversations = $conversations->map(function ($c) use ($activeOwnerId, &$chatHasMore, &$chatOldestId) {
                if ((int) $c->owner_id === (int) $activeOwnerId) {
                    $all = $c->messages;
                    if ($all->count() > self::PAGE_SIZE) {
                        $chatHasMore = true;
                        $c->messages = $all->slice(-self::PAGE_SIZE)->values();
                    }
                    $chatOldestId = optional($c->messages->first())->id;
                }
                return $c;
            });
        }

        return view('messages.index', [
            'contacts' => $contacts,
            'conversations' => $conversations,
            'activeOwnerId' => $activeOwnerId ? (int) $activeOwnerId : null,
            'chatHasMore' => $chatHasMore,
            'chatOldestId' => $chatOldestId,
        ]);
    }

    /**
     * AJAX endpoint used when the user scrolls to the top of the chat
     * window: fetches the previous page of older messages so the full
     * history loads progressively, WhatsApp-style, instead of all at once.
     */
    public function olderMessages(Request $request, $ownerId)
    {
        $user = Auth::user();
        $owner = Owner::findOrFail($ownerId);

        $this->authorizeOwnerThread($user, $owner);

        $beforeId = (int) $request->query('before_id', 0);

        $query = Message::where('property_id', $owner->property_id)
            ->where(function ($q) use ($owner) {
                $q->whereNull('owner_id')->orWhere('owner_id', $owner->id);
            });

        if ($beforeId > 0) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->orderByDesc('id')->limit(self::PAGE_SIZE)->get()->sortBy('id')->values();

        $totalBefore = Message::where('property_id', $owner->property_id)
            ->where(function ($q) use ($owner) {
                $q->whereNull('owner_id')->orWhere('owner_id', $owner->id);
            })
            ->when($beforeId > 0, fn ($q) => $q->where('id', '<', $beforeId))
            ->count();

        return response()->json([
            'messages' => $messages->map(function ($m) use ($user) {
                return [
                    'id' => $m->id,
                    'body' => $m->body,
                    'subject' => $m->subject,
                    'sender_type' => $m->sender_type,
                    'mine' => ($user->role === 'syndic' && $m->sender_type === 'syndic')
                        || ($user->role === 'copropriétaire' && $m->sender_type === 'owner'),
                    'time' => $m->created_at->format('H:i'),
                    'created_at' => $m->created_at->toIso8601String(),
                ];
            }),
            'has_more' => $totalBefore > $messages->count(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $property = null;
        $targetOwner = null;
        $broadcastToAll = $request->boolean('broadcast_to_all') || ($request->filled('owner_id') && $request->input('owner_id') === 'all');

        if ($user->role === 'syndic') {
            $property = $user->syndic?->property;
            if (! $property) {
                abort(403);
            }

            $targetOwners = collect();
            if ($broadcastToAll) {
                $targetOwners = Owner::where('property_id', $property->id)->get();
            } else {
                $targetOwner = Owner::findOrFail($request->input('owner_id'));
                if ($user->syndic->property_id !== $targetOwner->property_id) {
                    abort(403);
                }
                $targetOwners = collect([$targetOwner]);
            }
        } elseif ($user->role === 'copropriétaire') {
            $userOwner = $user->owner ?? null;
            if (! $userOwner) {
                abort(403);
            }

            $requestedOwnerId = $request->filled('owner_id') ? (int) $request->input('owner_id') : null;
            if ($requestedOwnerId !== null && $requestedOwnerId !== (int) $userOwner->id) {
                abort(403);
            }

            $property = $userOwner->property;
        } else {
            abort(403);
        }

        $messageOwnerId = null;
        if ($user->role === 'syndic') {
            $messageOwnerId = $broadcastToAll ? null : ($targetOwner?->id ?? null);
        } elseif ($user->role === 'copropriétaire') {
            $messageOwnerId = $userOwner->id;
        }

        $msg = Message::create([
            'property_id' => $property->id,
            'owner_id' => $messageOwnerId,
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'sender_type' => $user->role === 'syndic' ? 'syndic' : 'owner',
            'is_read' => false,
        ]);

        if ($user->role === 'syndic') {
            $service = new MeetingService();
            if ($broadcastToAll) {
                $service->notifyOwners($property, $targetOwners, 'Nouveau message: ' . ($msg->subject ?? ''), $msg->body);
            } elseif ($targetOwner) {
                $service->notifyOwners($property, [$targetOwner], 'Nouveau message: ' . ($msg->subject ?? ''), $msg->body);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $msg]);
        }

        $redirectOwnerId = $messageOwnerId ?? ($targetOwner?->id ?? ($user->owner?->id ?? null));

        return redirect()->route('messages.index', ['owner' => $redirectOwnerId]);
    }

    public function show($ownerId)
    {
        $user = Auth::user();
        $owner = Owner::findOrFail($ownerId);

        $this->authorizeOwnerThread($user, $owner);

        return redirect()->route('messages.index', ['owner' => $owner->id]);
    }

    /**
     * JSON polling endpoint used by the chat UI to fetch new messages
     * without a full page reload (mimics real-time delivery).
     */
    public function poll(Request $request, $ownerId)
    {
        $user = Auth::user();
        $owner = Owner::findOrFail($ownerId);

        $this->authorizeOwnerThread($user, $owner);

        $afterId = (int) $request->query('after_id', 0);

        $messages = Message::where('property_id', $owner->property_id)
            ->where(function ($query) use ($owner) {
                $query->whereNull('owner_id')->orWhere('owner_id', $owner->id);
            })
            ->where('id', '>', $afterId)
            ->orderBy('created_at')
            ->get();

        $this->markThreadRead($user, $owner->id);

        return response()->json([
            'messages' => $messages->map(function ($m) use ($user) {
                return [
                    'id' => $m->id,
                    'body' => $m->body,
                    'subject' => $m->subject,
                    'sender_type' => $m->sender_type,
                    'mine' => ($user->role === 'syndic' && $m->sender_type === 'syndic')
                        || ($user->role === 'copropriétaire' && $m->sender_type === 'owner'),
                    'time' => $m->created_at->format('H:i'),
                    'created_at' => $m->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    private function authorizeOwnerThread($user, Owner $owner): void
    {
        if ($user->role === 'syndic') {
            if ($user->syndic->property_id !== $owner->property_id) {
                abort(403);
            }
        } else {
            if (($user->owner->id ?? null) !== $owner->id) {
                abort(403);
            }
        }
    }

    /**
     * Mark as read the messages sent by "the other side" of the given thread.
     */
    private function markThreadRead($user, int $ownerId): void
    {
        $owner = Owner::find($ownerId);
        if (! $owner) {
            return;
        }

        if ($user->role === 'syndic') {
            // A syndic message row from an owner only ever has one reader
            // (the syndic), so a shared boolean is safe here.
            Message::where('property_id', $owner->property_id)
                ->where(function ($query) use ($owner) {
                    $query->whereNull('owner_id')->orWhere('owner_id', $owner->id);
                })
                ->where('sender_type', 'owner')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return;
        }

        // Owner side: a message can be a broadcast shared with other
        // owners, so record this owner's own read marker instead of
        // flipping a single is_read flag that every owner would share.
        $unreadIds = Message::where('property_id', $owner->property_id)
            ->where(function ($query) use ($owner) {
                $query->whereNull('owner_id')->orWhere('owner_id', $owner->id);
            })
            ->where('sender_type', 'syndic')
            ->whereDoesntHave('reads', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })
            ->pluck('id');

        if ($unreadIds->isEmpty()) {
            return;
        }

        $now = now();
        DB::table('message_reads')->insertOrIgnore(
            $unreadIds->map(fn ($id) => [
                'message_id' => $id,
                'owner_id' => $owner->id,
                'read_at' => $now,
            ])->all()
        );
    }
}