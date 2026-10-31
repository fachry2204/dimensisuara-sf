<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendStatusNotification;
use App\Models\StatusNotificationDelivery;
use App\Services\StatusNotificationTemplates;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StatusNotificationSettingsController extends Controller
{
    private function validateTemplate(Request $request, StatusNotificationTemplates $templates): array
    {
        $values = $request->validate([
            'event'=>['required',Rule::in(array_keys($templates->events()))],
            'channel_enabled'=>['required','boolean'],'event_enabled'=>['required','boolean'],
            'subject'=>['required','string','max:180','not_regex:/[\r\n]/'],
            'heading'=>['required','string','max:180','not_regex:/[\r\n]/'],
            'body'=>['required','string','max:8000'],
            'button'=>['required','string','max:100','not_regex:/[\r\n]/'],
            'footer'=>['required','string','max:1000'],
            'accent'=>['required','regex:/^#[a-fA-F0-9]{6}$/D'],
        ]);
        foreach (['subject','heading','body','button','footer'] as $field) {
            preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',$values[$field],$matches);
            if ($unknown = array_diff($matches[1],StatusNotificationTemplates::TOKENS)) {
                throw ValidationException::withMessages([$field=>'Placeholder tidak dikenal: '.implode(', ',$unknown)]);
            }
        }
        return $values;
    }

    private function configuration(Request $request, string $channel, StatusNotificationTemplates $templates): array
    {
        abort_unless(in_array($channel,['email','whatsapp'],true),404);
        $values = $this->validateTemplate($request,$templates);
        $configuration = $templates->configuration($channel);
        $configuration['enabled'] = $request->boolean('channel_enabled');
        $configuration['footer'] = $values['footer'];
        $configuration['accent'] = $values['accent'];
        $configuration['templates'][$values['event']] = array_merge(array_intersect_key($values,array_flip(['subject','heading','body','button'])),['enabled'=>$request->boolean('event_enabled')]);
        return $configuration;
    }

    public function save(Request $request, string $channel, StatusNotificationTemplates $templates, SystemSettings $settings)
    {
        $configuration = $this->configuration($request,$channel,$templates);
        if ($channel === 'whatsapp' && $configuration['enabled'] && !filled($settings->fonnteToken())) {
            throw ValidationException::withMessages(['channel_enabled'=>'Isi token Fonnte di Pengaturan Umum sebelum mengaktifkan notifikasi WhatsApp.']);
        }
        $settings->save(['status_notifications_'.$channel=>$configuration]);
        return redirect()->route('admin.system-settings.index',['tab'=>'notifications-'.$channel,'notification_event'=>$request->input('event')])->with('success','Pengaturan dan template notifikasi tersimpan di database.');
    }

    public function preview(Request $request, string $channel, StatusNotificationTemplates $templates)
    {
        abort_unless(in_array($channel,['email','whatsapp'],true),404);
        $request->validate(['event'=>['nullable','string',Rule::in(array_keys($templates->events()))]]);
        $event = $request->input('event','release.released');
        abort_unless(isset($templates->events()[$event]),422);
        $configuration = $request->isMethod('post') ? $this->configuration($request,$channel,$templates) : $templates->configuration($channel);
        $messageData = $templates->render($channel,$event,$templates->sample($event),$configuration);
        $view = $channel === 'email' ? 'emails.status-update' : 'admin.system-settings.whatsapp-preview';
        return response()->view($view,compact('messageData'))->header('Cache-Control','no-store, private')->header('X-Content-Type-Options','nosniff');
    }

    public function retry(Request $request, int $delivery)
    {
        $request->validate(['provider_checked'=>['accepted']]);
        $item = DB::transaction(function () use ($delivery) {
            $item = StatusNotificationDelivery::lockForUpdate()->findOrFail($delivery);
            abort_unless(in_array($item->status,['failed','skipped'],true),409,'Notifikasi ini sedang diproses atau sudah dikirim.');
            $item->update(['status'=>'pending','error'=>null]);
            SendStatusNotification::dispatch($item->id)->afterCommit();
            return $item;
        });
        return redirect()->route('admin.system-settings.index',['tab'=>'notifications-'.$item->channel,'notification_event'=>$item->event])->with('success','Notifikasi dijadwalkan ulang.');
    }
}
