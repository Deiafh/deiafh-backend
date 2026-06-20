<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstallController extends Controller
{
    /**
     * Marker written once the site has been installed. Lives on the local disk
     * so the "installed" state holds even before/independently of the database.
     */
    private const LOCK = 'installed.lock';

    private function isInstalled(): bool
    {
        return Storage::disk('local')->exists(self::LOCK);
    }

    /**
     * Public: lets the front decide between the "coming soon" page and the
     * normal site. No secret required — leaks nothing sensitive.
     */
    public function status()
    {
        return response()->json(['installed' => $this->isInstalled()]);
    }

    /**
     * Run the first-time installation: migrate, seed permissions/role and the
     * default Setting/Theme rows, then create the super-admin account.
     */
    public function install(Request $request)
    {
        if ($this->isInstalled()) {
            return response()->json(['message' => 'Application is already installed.'], 409);
        }

        // Gate on the configured secret. An unset secret disables installs entirely.
        $configured = config('install.secret');
        $supplied = $request->header('X-Install-Secret') ?: $request->input('secret');

        if (empty($configured) || empty($supplied) || ! hash_equals((string) $configured, (string) $supplied)) {
            return response()->json(['message' => 'Invalid installation secret.'], 403);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Schema first — the DB may be empty on a brand-new deploy.
            Artisan::call('migrate', ['--force' => true]);

            // Idempotent: creates every permission + the super_admin role.
            Artisan::call('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);

            DB::transaction(function () use ($validated) {
                $this->ensureDefaultSetting();
                $this->ensureDefaultTheme();

                $user = User::create([
                    'name'     => $validated['name'],
                    'username' => $validated['username'],
                    'password' => $validated['password'], // hashed by the model cast
                ]);

                $user->assignRole(config('permissions.super_admin_role', 'super_admin'));
            });
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Installation failed: ' . $e->getMessage(),
            ], 500);
        }

        Storage::disk('local')->put(self::LOCK, now()->toIso8601String());

        return response()->json([
            'message'  => 'Installation completed successfully.',
            'username' => $validated['username'],
        ]);
    }

    /**
     * Minimal Setting row so the front configurations endpoint has data to serve.
     */
    private function ensureDefaultSetting(): void
    {
        Setting::firstOrCreate([], [
            'title'                 => config('app.name', 'Restaurant'),
            'logo'                  => '',
            'background'            => '',
            'order_min'             => 0,
            'is_whatsapp_available' => false,
            'currency'              => 'EGP',
            'time_zone'             => config('app.timezone', 'Africa/Cairo'),
            'dir'                   => 'rtl',
            'lang'                  => 'ar',
        ]);
    }

    /**
     * Minimal Theme row (placeholder colors) so the UI renders before the
     * operator customizes it from the dashboard.
     */
    private function ensureDefaultTheme(): void
    {
        if (Theme::query()->exists()) {
            return;
        }

        $dark = '#000000';
        $light = '#ffffff';

        Theme::create([
            'header'                  => $light,
            'footer'                  => $light,
            'icon'                    => $dark,
            'icon_back'               => $light,
            'icon_border'             => $dark,
            'button_back'             => $dark,
            'button_color'            => $light,
            'cat_header_back'         => $light,
            'cat_header_color'        => $dark,
            'cat_header_active_back'  => $dark,
            'cat_header_active_color' => $light,
            'order_footer_back'       => $light,
            'order_footer_color'      => $dark,
            'order_footer_n_back'     => $dark,
            'order_footer_n_color'    => $light,
            'footer_color'            => $dark,
            'radio_border'            => $dark,
            'radio_back'              => $light,
            'radio_color'             => $dark,
            'text'                    => $dark,
            'modal_header_back'       => $dark,
            'modal_header_color'      => $light,
        ]);
    }
}
