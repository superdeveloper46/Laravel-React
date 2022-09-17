<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Lead;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;

class ReminderNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification for Reminder';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now = Carbon::now()->format('Y-m-d H:i');
        Log::info("Start reminder cron job", array('now' => $now));
        $reminders = Reminder::where('time', '=', $now)->get();
        foreach ($reminders as $reminder) {
            $tokenList = Device::getTokenListFromAgentIds([$reminder->agent_id]);
            $notification = [
                'title' => $reminder->lead->fullname? $reminder->lead->fullname: 'New Reminder',
                'body' => $reminder->name,
                'sound' => true,
                'badge' => 1
            ];
            $data = [
                'url'=> '/companies/leads/all'
            ];
            Lead::notification($tokenList, $notification, $data);
        }
    }
}
