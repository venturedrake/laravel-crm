<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $widget->name }}</title>
    <style>
        :root { --c: {{ $widget->color ?: '#2563eb' }}; }
        * { box-sizing: border-box; }
        html, body { margin:0; height:100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background:#fff; color:#1f2937; }
        .lcrm { display:flex; flex-direction:column; height:100vh; font-size:14px; }
        .lcrm-header { background:var(--c); color:#fff; padding:12px 16px; }
        .lcrm-header h1 { font-size:15px; margin:0 0 2px; font-weight:600; }
        .lcrm-header p  { font-size:12px; margin:0; opacity:.9; }
        .lcrm-id { display:none; padding:10px 12px; border-bottom:1px solid #e5e7eb; background:#f9fafb; gap:6px; }
        .lcrm-id.show { display:grid; }
        .lcrm-id input, .lcrm-msg input { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; outline:none; }
        .lcrm-id input:focus, .lcrm-msg input:focus { border-color: var(--c); }
        .lcrm-id button, .lcrm-msg button { padding:8px 14px; border:0; border-radius:6px; background:var(--c); color:#fff; font-size:13px; cursor:pointer; }
        .lcrm-id button:hover, .lcrm-msg button:hover { filter:brightness(1.1); }
        .lcrm-body { flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:8px; }
        .lcrm-empty { text-align:center; opacity:.6; font-size:12px; margin-top:30px; }
        .lcrm-bubble { max-width:80%; padding:8px 12px; border-radius:14px; word-wrap:break-word; white-space:pre-wrap; }
        .lcrm-bubble.me { align-self:flex-end; background:var(--c); color:#fff; border-bottom-right-radius:4px; }
        .lcrm-bubble.them { align-self:flex-start; background:#f3f4f6; color:#1f2937; border-bottom-left-radius:4px; }
        .lcrm-bubble small { display:block; font-size:10px; opacity:.7; margin-top:3px; }
        .lcrm-msg { display:flex; gap:6px; padding:10px; border-top:1px solid #e5e7eb; }
        .lcrm-msg input { flex:1; }
        .lcrm-status { padding:8px; text-align:center; font-size:12px; opacity:.6; border-top:1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="lcrm">
    <div class="lcrm-header">
        <h1>{{ $widget->name }}</h1>
        @if($widget->welcome_message)
            <p>{{ $widget->welcome_message }}</p>
        @endif
    </div>

    <form id="lcrm-id-form" class="lcrm-id">
        <input type="text"  name="name"  placeholder="Your name"  autocomplete="name">
        <input type="email" name="email" placeholder="Your email" autocomplete="email">
        <button type="submit">Start chat</button>
    </form>

    <div id="lcrm-body" class="lcrm-body">
        <div class="lcrm-empty">Loading…</div>
    </div>

    <form id="lcrm-msg-form" class="lcrm-msg">
        <input type="text" name="body" placeholder="Type a message…" autocomplete="off" required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
(function(){
    var API = @json($apiBase);
    var STORAGE_KEY = 'lcrm_chat_token_' + @json($widget->public_key);

    var token = null;
    var lastId = 0;
    var pollTimer = null;
    var bodyEl = document.getElementById('lcrm-body');
    var idForm = document.getElementById('lcrm-id-form');
    var msgForm = document.getElementById('lcrm-msg-form');

    try { token = localStorage.getItem(STORAGE_KEY); } catch(e) {}

    function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, function(c){
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    }); }

    function fmtTime(iso){
        if (!iso) return '';
        try { return new Date(iso).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); } catch(e){ return ''; }
    }

    function render(messages){
        if (!messages || !messages.length) {
            bodyEl.innerHTML = '<div class="lcrm-empty">{{ $widget->welcome_message ?: 'How can we help?' }}</div>';
            return;
        }
        bodyEl.innerHTML = messages.map(function(m){
            var mine = m.sender_type === 'visitor';
            return '<div class="lcrm-bubble '+(mine?'me':'them')+'">'+
                escapeHtml(m.body)+
                '<small>'+escapeHtml(m.sender_name)+' · '+fmtTime(m.created_at)+'</small>'+
                '</div>';
        }).join('');
        bodyEl.scrollTop = bodyEl.scrollHeight;
        var max = messages[messages.length-1].id;
        if (max > lastId) lastId = max;
    }

    function appendMessages(messages){
        if (!messages || !messages.length) return;
        var empty = bodyEl.querySelector('.lcrm-empty');
        if (empty) bodyEl.innerHTML = '';
        messages.forEach(function(m){
            var mine = m.sender_type === 'visitor';
            var div = document.createElement('div');
            div.className = 'lcrm-bubble ' + (mine ? 'me' : 'them');
            div.innerHTML = escapeHtml(m.body) + '<small>'+escapeHtml(m.sender_name)+' · '+fmtTime(m.created_at)+'</small>';
            bodyEl.appendChild(div);
            if (m.id > lastId) lastId = m.id;
        });
        bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    function postJSON(url, data){
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'Accept':'application/json'},
            body: JSON.stringify(data || {})
        }).then(function(r){
            if (!r.ok) throw new Error('HTTP '+r.status);
            return r.json();
        });
    }

    function init(){
        postJSON(API + '/init', {
            visitor_token: token,
            current_url: document.referrer || null
        }).then(function(data){
            token = data.visitor_token;
            try { localStorage.setItem(STORAGE_KEY, token); } catch(e) {}

            // Show identity capture if visitor hasn't given name/email
            if (!data.visitor.name && !data.visitor.email) {
                idForm.classList.add('show');
            }

            render(data.messages);
            startPolling();
        }).catch(function(err){
            bodyEl.innerHTML = '<div class="lcrm-empty">Unable to connect. Please try again later.</div>';
            console.error('[lcrm-chat] init failed', err);
        });
    }

    function poll(){
        if (!token) return;
        fetch(API + '/messages?token='+encodeURIComponent(token)+'&since_id='+lastId, {
            headers: {'Accept':'application/json'}
        }).then(function(r){ return r.ok ? r.json() : null; })
        .then(function(data){ if (data && data.messages) appendMessages(data.messages); })
        .catch(function(){ /* network blip — keep polling */ });
    }

    function startPolling(){
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(poll, 4000);
    }

    idForm.addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(idForm);
        postJSON(API + '/identify', {
            token: token,
            name: fd.get('name'),
            email: fd.get('email')
        }).then(function(){ idForm.classList.remove('show'); });
    });

    msgForm.addEventListener('submit', function(e){
        e.preventDefault();
        var input = msgForm.querySelector('input[name=body]');
        var body = (input.value || '').trim();
        if (!body) return;
        input.value = '';
        // Optimistic append
        appendMessages([{ id: lastId + 0.1, sender_type:'visitor', sender_name:'You', body: body, created_at: new Date().toISOString() }]);
        postJSON(API + '/messages/send', { token: token, body: body })
            .catch(function(err){ console.error('[lcrm-chat] send failed', err); });
    });

    init();
})();
</script>
</body>
</html>

