<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LeadStatusUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:update:to:missed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating Lead status...';

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
        $timeLimitHours = 12; // if leads are oldest then $timeLimitHours limit than we Move them to status MISSED
        $new = LeadStatus::where('type', LeadStatus::$STATUS_NEW)->firstOrFail();
        $viewed = LeadStatus::where('type', LeadStatus::$STATUS_VIEWED)->firstOrFail();
        $missed = LeadStatus::where('type', LeadStatus::$STATUS_MISSED)->firstOrFail();

        Lead::whereRaw("((TIMESTAMPDIFF(SECOND, created_at, NOW()) / 60) / 60) >= ?", [$timeLimitHours])
            ->whereIn('lead_status_id', [$new->id, $viewed->id])
            ->update(['lead_status_id' => $missed->id]);

        $this->info('***Lead status updated!***');
    }
}
