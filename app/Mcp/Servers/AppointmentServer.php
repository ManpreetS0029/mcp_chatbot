<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BookAppointment;
use App\Mcp\Tools\GetAvailableSlots;
use App\Mcp\Tools\GetBookingDetails;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Appointment Server')]
#[Version('0.0.1')]
#[Instructions('Instructions describing how to use the server and its features.')]
class AppointmentServer extends Server
{
    protected array $tools = [
        GetAvailableSlots::class,
        GetBookingDetails::class,
        BookAppointment::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
