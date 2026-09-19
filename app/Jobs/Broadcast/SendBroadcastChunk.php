<?php

namespace App\Jobs\Broadcast;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

use App\Models\User\User;
use App\Models\Broadcast;
use App\Notifications\Admin\Broadcast\BroadcastMessage;

class SendBroadcastChunk implements ShouldQueue
{
    use Queueable, Batchable;

    public $timeout = 300; // 5 minutes max
    public $tries = 3;     // Retry up to 3 times

    protected $broadcastId;
    protected $userIds;
    protected $chunkNumber;
    protected $totalChunks;

    /**
     * Create a new job instance.
     */
    public function __construct($broadcastId, array $userIds, $chunkNumber, $totalChunks)
    {
        $this->broadcastId = $broadcastId;
        $this->userIds = $userIds;
        $this->chunkNumber = $chunkNumber;
        $this->totalChunks = $totalChunks;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $broadcast = Broadcast::findOrFail($this->broadcastId);

            // Load only users in this chunk
            $recipients = User::whereIn('id', $this->userIds)
                ->whereHas('profile')
                ->get();

            $delay = 0;
            if ($recipients->count() > 0) {
                foreach($recipients as $index => $recipient) {
                    $delay += rand(5, 15);

                    Notification::send(
                        $recipient, 
                        (new BroadcastMessage($broadcast))
                            ->delay(now()->addSeconds($delay))
                    );
                }
            }

            Log::info("Broadcast chunk {$this->chunkNumber}/{$this->totalChunks} sent", [
                'broadcast_id' => $this->broadcastId,
                'recipients' => $recipients->count(),
            ]);

            // Increment completed chunk counter for progress tracking
            $broadcast->increment('completed_chunks');
        } catch (\Exception $e) {
            Log::error("Failed to send broadcast chunk {$this->chunkNumber}/{$this->totalChunks}", [
                'broadcast_id' => $this->broadcastId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
