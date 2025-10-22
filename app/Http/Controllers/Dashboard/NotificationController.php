<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Show the notification settings form.
     */
    public function show()
    {
        $tenant = tenant();
        
        return view('dashboard.notifications', compact('tenant'));
    }

    /**
     * Update notification settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'notifications' => 'required|array',
            'notifications.failed_orders' => 'boolean',
            'notifications.usage_limits' => 'boolean', 
            'notifications.payment_failures' => 'boolean',
            'notifications.team_members' => 'boolean',
            'notifications.weekly_summary' => 'boolean',
            'notifications.system_updates' => 'boolean',
            'recipients' => 'required|in:admins_only,all_members,custom',
            'custom_emails' => 'nullable|string',
        ]);

        $tenant = tenant();
        
        // Update tenant settings
        $settings = $tenant->settings ?? [];
        $settings['notifications'] = $validated['notifications'];
        $settings['notification_recipients'] = $validated['recipients'];
        
        if ($validated['recipients'] === 'custom') {
            // Validate custom emails
            if (!empty($validated['custom_emails'])) {
                $emails = array_map('trim', explode(',', $validated['custom_emails']));
                foreach ($emails as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return back()->withErrors([
                            'custom_emails' => 'One or more email addresses are invalid'
                        ])->withInput();
                    }
                }
                $settings['custom_notification_emails'] = $validated['custom_emails'];
            }
        } else {
            $settings['custom_notification_emails'] = null;
        }
        
        $tenant->settings = $settings;
        $tenant->save();

        // Log activity
        \App\Services\UserActivityService::log('notification_settings.updated', null, [
            'settings' => $validated['notifications'],
            'recipients' => $validated['recipients'],
        ]);

        return back()->with('success', 'Notification settings updated successfully!');
    }
}
