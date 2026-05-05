<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use VentureDrake\LaravelCrm\Http\Controllers\Controller;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatMessage;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrm\Services\ChatService;

/**
 * Public chat widget endpoints. NONE of these use sessions or CSRF —
 * the visitor is authenticated by an opaque visitor_token kept in the
 * iframe's localStorage. This is what makes the widget embeddable on
 * any third-party site without "419 Page Expired" errors.
 */
class ChatWidgetEmbedController extends Controller
{
    /**
     * GET /p/chat/{publicKey}.js — JS loader injected via:
     *   <script async src="https://your-crm.com/p/chat/{publicKey}.js"></script>
     */
    public function script(string $publicKey): Response
    {
        $widget = $this->resolveWidget($publicKey);

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
     * GET /p/chat/{publicKey} — the iframe HTML page.
     * Pure HTML + vanilla JS. No session, no CSRF, no Livewire.
     */
    public function widget(Request $request, string $publicKey)
    {
        $widget = $this->resolveWidget($publicKey);

        return response()->view('laravel-crm::chat.widget', [
            'widget' => $widget,
            'apiBase' => url('p/chat/'.$publicKey),
        ])->withHeaders([
            // Allow this page to be iframed from any origin
            'Content-Security-Policy' => 'frame-ancestors *',
        ]);
    }

    /**
     * POST /p/chat/{publicKey}/init
     * Body: { visitor_token?, current_url? }
     */
    public function init(Request $request, string $publicKey): JsonResponse
    {
        $widget = $this->resolveWidget($publicKey);
        $service = app(ChatService::class);

        $visitor = $service->findOrCreateVisitor($widget, $request->input('visitor_token'), [
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'current_url' => $request->input('current_url'),
        ]);

        $conversation = $service->openConversationForVisitor($visitor);

        return $this->cors(response()->json([
            'visitor_token' => $visitor->visitor_token,
            'visitor' => [
                'name' => $visitor->name,
                'email' => $visitor->email,
            ],
            'conversation' => [
                'external_id' => $conversation->external_id,
                'channel' => $conversation->channelName(),
                'status' => $conversation->status,
            ],
            'widget' => [
                'name' => $widget->name,
                'welcome_message' => $widget->welcome_message,
                'color' => $widget->color,
            ],
            'messages' => $this->serializeMessages($conversation->messages()->get()),
        ]));
    }

    /**
     * GET /p/chat/{publicKey}/messages?token=...&since_id=...
     */
    public function messages(Request $request, string $publicKey): JsonResponse
    {
        [, , $conversation] = $this->resolveContext($request, $publicKey);

        $sinceId = (int) $request->query('since_id', 0);

        $messages = $conversation->messages()
            ->when($sinceId, fn ($q) => $q->where('id', '>', $sinceId))
            ->get();

        return $this->cors(response()->json([
            'messages' => $this->serializeMessages($messages),
            'status' => $conversation->status,
        ]));
    }

    /**
     * POST /p/chat/{publicKey}/messages   Body: { token, body }
     */
    public function send(Request $request, string $publicKey): JsonResponse
    {
        [, , $conversation] = $this->resolveContext($request, $publicKey);

        $body = trim((string) $request->input('body', ''));
        if ($body === '') {
            return $this->cors(response()->json(['error' => 'Empty message'], 422));
        }
        $body = mb_substr($body, 0, 5000);

        $message = app(ChatService::class)->sendVisitorMessage($conversation, $body);

        return $this->cors(response()->json([
            'message' => $this->serializeMessages(collect([$message]))[0],
        ]));
    }

    /**
     * POST /p/chat/{publicKey}/identify   Body: { token, name?, email? }
     */
    public function identify(Request $request, string $publicKey): JsonResponse
    {
        [, $visitor] = $this->resolveContext($request, $publicKey);

        $visitor->update([
            'name' => trim((string) $request->input('name')) ?: $visitor->name,
            'email' => trim((string) $request->input('email')) ?: $visitor->email,
        ]);

        return $this->cors(response()->json([
            'visitor' => [
                'name' => $visitor->name,
                'email' => $visitor->email,
            ],
        ]));
    }

    /** OPTIONS preflight */
    public function preflight(): Response
    {
        return $this->cors(response('', 204));
    }

    // ---------- helpers ----------

    protected function resolveWidget(string $publicKey): ChatWidget
    {
        return ChatWidget::where('public_key', $publicKey)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return array{0: ChatWidget, 1: ChatVisitor, 2: ChatConversation}
     */
    protected function resolveContext(Request $request, string $publicKey): array
    {
        $widget = $this->resolveWidget($publicKey);

        $token = $request->input('token')
            ?: $request->query('token')
            ?: $request->header('X-Visitor-Token');

        if (! $token) {
            abort(response()->json(['error' => 'Missing token'], 401)->withHeaders([
                'Access-Control-Allow-Origin' => '*',
            ]));
        }

        $visitor = ChatVisitor::where('chat_widget_id', $widget->id)
            ->where('visitor_token', $token)
            ->first();

        if (! $visitor) {
            abort(response()->json(['error' => 'Invalid token'], 401)->withHeaders([
                'Access-Control-Allow-Origin' => '*',
            ]));
        }

        $visitor->forceFill(['last_seen_at' => now()])->save();

        $conversation = app(ChatService::class)->openConversationForVisitor($visitor);

        return [$widget, $visitor, $conversation];
    }

    protected function serializeMessages($messages): array
    {
        return $messages->map(fn (ChatMessage $m) => [
            'id' => $m->id,
            'sender_type' => $m->sender_type,
            'sender_name' => $m->senderName(),
            'body' => $m->body,
            'created_at' => $m->created_at?->toIso8601String(),
        ])->values()->all();
    }

    protected function cors(JsonResponse|Response $response): JsonResponse|Response
    {
        return $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Visitor-Token',
        ]);
    }
}
