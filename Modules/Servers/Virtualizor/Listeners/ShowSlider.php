<?php

namespace Modules\Servers\Virtualizor\Listeners;

use App\Events\ClientAreaProductDetailsOutput;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ShowSlider
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ClientAreaProductDetailsOutput $event
     * @return void
     */
    public function handle(ClientAreaProductDetailsOutput $event)
    {
        //
        $service = $event->service;
        $services = new \App\Helpers\Service($service->id, $service->userid);
        $data = [
            'pid' => $service->id,
            'userid' => $service->userid
        ];

        if (strtolower($services->getModule()) == 'virtualizor' && $services->getData("status") == "Active") {
            return view('virtualizor::slider', compact('service'))->with($data);
        }
    }
}
