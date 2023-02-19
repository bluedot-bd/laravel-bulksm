<?php

namespace BluedotBd\LaravelBulksms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaravelBulksms;

class SendSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $config, $number, $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($config, $number, $message)
    {
        $this->message = $message;
        $this->number  = $number;
        $this->config  = $config;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sms = new LaravelBulksms($this->config);
        $sms->to($this->number)->message($this->message)->send();
    }
}
