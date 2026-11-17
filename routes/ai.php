<?php

use App\Mcp\Servers\AppointmentServer;
use Laravel\Mcp\Facades\Mcp;

// Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);

Mcp::web('/mcp/appointments', AppointmentServer::class);
Mcp::local('appointments', AppointmentServer::class);
