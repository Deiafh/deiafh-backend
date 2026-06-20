<?php

/*
|--------------------------------------------------------------------------
| First-run installer
|--------------------------------------------------------------------------
|
| The /api/install endpoint is gated by this secret. Set a strong value in
| .env per deployment. An empty secret disables the installer entirely
| (the endpoint refuses), so the site can never be installed without it.
|
*/

return [
    'secret' => env('INSTALL_SECRET'),
];
