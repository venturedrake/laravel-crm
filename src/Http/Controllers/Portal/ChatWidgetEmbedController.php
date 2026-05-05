<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use VentureDrake\LaravelCrm\Http\Controllers\Controller;
use VentureDrake\LaravelCrm\Models\ChatWidget;

class ChatWidgetEmbedController extends Controller
{
    /**
     * Serve the JS embed loader. Placed on a customer site via:
     *   <script src="https://your-crm.com/p/chat/{publicKey}.js"></script>
     * The script injects an iframe pointing at /p/chat/{publicKey}.
     */
    public function script(string $publicKey): Response
    {
        $widget = ChatWidget::where('public_key', $publicKey)
            ->where('is_active', true)
            ->firstOrFail();

        $iframeUrl = url(route('laravel-crm.portal.chat.widget', ['publicKey' => $publicKey]));
        $color = e($widget->color ?: '#2563eb');
        $position = $widget->position === 'bottom-left' ? 'left:20px;' : 'right:20px;';

        $js = <<<JS
(function(){
    if (window.__lcrmChatLoaded) return; window.__lcrmChatLoaded = true;

    var btn = document.createElement('button');
    btn.setAttribute('aria-label','Open chat');
    btn.style.cssText = 'position:fixed;bottom:20px;{$position}width:60px;height:60px;border-radius:50%;background:{$color};color:#fff;border:0;box-shadow:0 4px 14px rgba(0,0,0,.2);cursor:pointer;z-index:2147483646;font-size:28px;';
    btn.innerHTML = '\u{1F4AC}';

    var iframe = document.createElement('iframe');
    iframe.src = '{$iframeUrl}';
    iframe.style.cssText = 'position:fixed;bottom:90px;{$position}width:380px;height:560px;max-height:80vh;border:0;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.25);z-index:2147483647;display:none;background:#fff;';
    iframe.setAttribute('title','Chat with us');

    var open = false;
    btn.addEventListener('click', function(){
        open = !open;
        iframe.style.display = open ? 'block' : 'none';
    });

    function inject(){ document.body.appendChild(iframe); document.body.appendChild(btn); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inject);
    } else { inject(); }
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * The widget iframe page (the actual chat UI loaded inside the iframe).
     */
    public function widget(Request $request, string $publicKey)
    {
        $widget = ChatWidget::where('public_key', $publicKey)
            ->where('is_active', true)
            ->firstOrFail();

        // visitor token persisted via cookie on the embed origin
        $visitorToken = $request->cookie('lcrm_chat_token_'.$publicKey);

        $response = response()->view('laravel-crm::chat.widget', [
            'widget' => $widget,
            'visitorToken' => $visitorToken,
        ]);

        if (! $visitorToken) {
            $response->cookie('lcrm_chat_token_'.$publicKey, \Illuminate\Support\Str::random(40), 60 * 24 * 365);
        }

        return $response;
    }
}

