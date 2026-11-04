<x-app-layout>
    <x-slot name="title">Graph</x-slot>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -my-7 h-[calc(100vh-4rem)] relative overflow-hidden"
         x-data="{ show: { link: true, tag: true, semantic: true }, hover: null }">
        <canvas id="graph" class="absolute inset-0 w-full h-full cursor-grab active:cursor-grabbing"></canvas>

        {{-- Legend / controls --}}
        <div class="absolute top-5 left-5 card ticks w-64 p-4 space-y-3 shadow-md">
            <div>
                <div class="eyebrow">Knowledge graph</div>
                <div class="mt-1 font-mono text-[11px] text-zinc-500">{{ str_pad(count($graph['nodes']), 2, '0', STR_PAD_LEFT) }} notes · {{ str_pad(count($graph['edges']), 2, '0', STR_PAD_LEFT) }} connections</div>
            </div>
            <div class="space-y-1.5 pt-3 border-t border-dashed border-zinc-200">
                @foreach (['link' => ['Your links', 'bg-zinc-900', 'h-[2px]'], 'semantic' => ['Similar meaning (AI)', 'bg-brand-500', 'h-[2px]'], 'tag' => ['Shared tag', 'bg-zinc-300', 'h-px']] as $kind => [$label, $color, $h])
                    <label class="flex items-center gap-2.5 cursor-pointer text-sm text-zinc-700 select-none">
                        <input type="checkbox" x-model="show.{{ $kind }}" @change="window.graphFilter(show)" class="rounded-none border-zinc-400 text-brand-500 focus:ring-brand-500">
                        <span class="w-5 {{ $h }} {{ $color }} {{ $kind === 'semantic' ? '[background:repeating-linear-gradient(90deg,#f2650f_0_4px,transparent_4px_7px)]' : '' }}"></span>
                        <span class="flex-1">{{ $label }}</span>
                        <span class="font-mono text-[10px] text-zinc-400">{{ $counts[$kind] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
            <p class="font-mono text-[10px] text-zinc-400 leading-relaxed pt-3 border-t border-dashed border-zinc-200">drag to pan · scroll to zoom<br>drag a node to move it · click to open</p>
        </div>

        {{-- Hover card --}}
        <div id="graph-tip" class="hidden absolute pointer-events-none card shadow-md px-3 py-2 max-w-xs">
            <div class="font-mono text-[10px] tracking-wider text-zinc-400" data-meta></div>
            <div class="text-sm font-medium text-zinc-900" data-title></div>
            <div class="font-mono text-[10.5px] text-zinc-500" data-tags></div>
        </div>

        @if (count($graph['nodes']) === 0)
            <div class="absolute inset-0 grid place-items-center">
                <div class="card p-8 text-center">
                    <p class="font-mono text-xs text-zinc-400">// graph is empty</p>
                    <a href="{{ route('notes.create') }}" class="btn-primary mt-4">Write a note</a>
                </div>
            </div>
        @endif
    </div>

    <script>
    (() => {
        const data = @json($graph);
        const canvas = document.getElementById('graph'), ctx = canvas.getContext('2d'), tip = document.getElementById('graph-tip');
        const C = { ink: '#16140f', muted: '#7a7262', hair: '#e3dccb', tag: '#cfc6b1', accent: '#f2650f', paper: '#fffdf8', grid: 'rgba(22,20,15,0.05)' };
        let W = 0, H = 0, dpr = window.devicePixelRatio || 1;
        const view = { x: 0, y: 0, k: 1 };

        // Nodes: degree drives size. Seed positions on a circle for a stable start.
        const byId = new Map();
        data.nodes.forEach((n, i) => {
            const a = (i / data.nodes.length) * Math.PI * 2;
            Object.assign(n, { x: Math.cos(a) * 200, y: Math.sin(a) * 200, vx: 0, vy: 0, deg: 0 });
            byId.set(n.id, n);
        });
        const edges = data.edges.map(e => ({ ...e, s: byId.get(e.source), t: byId.get(e.target) })).filter(e => e.s && e.t);
        edges.forEach(e => { e.s.deg++; e.t.deg++; });
        const radius = n => 5 + Math.min(10, Math.sqrt(n.deg) * 2.6);
        let visible = { link: true, tag: true, semantic: true };
        window.graphFilter = (v) => { visible = { ...v }; alpha = 0.6; };

        // Force simulation (repulsion, springs, centering) with cooling.
        let alpha = 1;
        const strength = { link: 0.05, semantic: 0.03, tag: 0.015 }, rest = { link: 140, semantic: 170, tag: 190 };
        function tick() {
            const ns = data.nodes;
            for (let i = 0; i < ns.length; i++) for (let j = i + 1; j < ns.length; j++) {
                const a = ns[i], b = ns[j]; let dx = b.x - a.x, dy = b.y - a.y, d2 = dx * dx + dy * dy || 0.01;
                const f = Math.min(9000 / d2, 10) * alpha, d = Math.sqrt(d2);
                dx /= d; dy /= d; a.vx -= dx * f; a.vy -= dy * f; b.vx += dx * f; b.vy += dy * f;
            }
            for (const e of edges) {
                if (!visible[e.kind]) continue;
                const dx = e.t.x - e.s.x, dy = e.t.y - e.s.y, d = Math.hypot(dx, dy) || 1;
                const f = (d - rest[e.kind]) * strength[e.kind] * alpha;
                e.s.vx += dx / d * f; e.s.vy += dy / d * f; e.t.vx -= dx / d * f; e.t.vy -= dy / d * f;
            }
            // Label collision: labels extend to the right of each node, so keep nodes whose
            // label boxes would overlap apart vertically (and a little horizontally).
            for (let i = 0; i < ns.length; i++) for (let j = i + 1; j < ns.length; j++) {
                const a = ns[i], b = ns[j], dx = b.x - a.x, dy = b.y - a.y;
                const left = dx >= 0 ? a : b, span = (left.lw || 120) + 24;
                if (Math.abs(dx) < span && Math.abs(dy) < 26) {
                    const push = (26 - Math.abs(dy)) * 0.18, dir = dy >= 0 ? 1 : -1;
                    a.vy -= dir * push; b.vy += dir * push;
                    a.vx -= Math.sign(dx || 1) * push * 0.3; b.vx += Math.sign(dx || 1) * push * 0.3;
                }
            }
            for (const n of ns) {
                n.vx -= n.x * 0.0025 * alpha; n.vy -= n.y * 0.0025 * alpha;
                if (n !== drag) { n.x += n.vx; n.y += n.vy; }
                n.vx *= 0.82; n.vy *= 0.82;
            }
            alpha = Math.max(alpha * 0.985, 0.02);
        }

        function resize() {
            const r = canvas.getBoundingClientRect(); W = r.width; H = r.height;
            canvas.width = W * dpr; canvas.height = H * dpr;
        }
        const toScreen = (x, y) => [(x * view.k) + W / 2 + view.x, (y * view.k) + H / 2 + view.y];
        const toWorld = (sx, sy) => [(sx - W / 2 - view.x) / view.k, (sy - H / 2 - view.y) / view.k];

        function draw() {
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.clearRect(0, 0, W, H);
            // grid
            const step = 24 * view.k; ctx.strokeStyle = C.grid; ctx.lineWidth = 1; ctx.beginPath();
            for (let x = ((W / 2 + view.x) % step); x < W; x += step) { ctx.moveTo(x, 0); ctx.lineTo(x, H); }
            for (let y = ((H / 2 + view.y) % step); y < H; y += step) { ctx.moveTo(0, y); ctx.lineTo(W, y); }
            ctx.stroke();

            const focus = hoverNode;
            const near = new Set(focus ? edges.filter(e => visible[e.kind] && (e.s === focus || e.t === focus)).flatMap(e => [e.s, e.t]) : []);

            for (const e of edges) {
                if (!visible[e.kind]) continue;
                const [x1, y1] = toScreen(e.s.x, e.s.y), [x2, y2] = toScreen(e.t.x, e.t.y);
                const active = !focus || e.s === focus || e.t === focus;
                ctx.globalAlpha = active ? 1 : 0.12;
                ctx.setLineDash(e.kind === 'semantic' ? [4, 3] : []);
                ctx.strokeStyle = e.kind === 'link' ? C.ink : e.kind === 'semantic' ? C.accent : C.tag;
                ctx.lineWidth = e.kind === 'tag' ? 1 : 1.6;
                ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
                if (e.kind === 'link' && active) { // arrowhead
                    const a = Math.atan2(y2 - y1, x2 - x1), r = radius(e.t) * Math.min(view.k, 1.4) + 3;
                    const ax = x2 - Math.cos(a) * r, ay = y2 - Math.sin(a) * r;
                    ctx.setLineDash([]); ctx.fillStyle = C.ink; ctx.beginPath();
                    ctx.moveTo(ax, ay); ctx.lineTo(ax - Math.cos(a - 0.45) * 7, ay - Math.sin(a - 0.45) * 7); ctx.lineTo(ax - Math.cos(a + 0.45) * 7, ay - Math.sin(a + 0.45) * 7); ctx.fill();
                }
            }
            ctx.setLineDash([]); ctx.globalAlpha = 1;

            for (const n of data.nodes) {
                const [x, y] = toScreen(n.x, n.y), r = radius(n) * Math.min(view.k, 1.4);
                const dim = focus && n !== focus && !near.has(n);
                ctx.globalAlpha = dim ? 0.2 : 1;
                ctx.fillStyle = n === focus ? C.accent : (n.pinned ? C.accent : C.paper);
                ctx.strokeStyle = C.ink; ctx.lineWidth = 1.5;
                ctx.beginPath(); ctx.rect(x - r, y - r, r * 2, r * 2); ctx.fill(); ctx.stroke(); // square nodes
                if (view.k > 0.55 || n === focus || near.has(n)) {
                    ctx.font = `${n === focus ? 600 : 500} 12px "Space Grotesk", sans-serif`;
                    ctx.fillStyle = C.ink; ctx.textBaseline = 'middle';
                    const label = n.title.length > 28 ? n.title.slice(0, 27) + '…' : n.title;
                    ctx.fillStyle = 'rgba(251,248,241,0.85)'; const w = ctx.measureText(label).width;
                    ctx.fillRect(x + r + 4, y - 8, w + 6, 16);
                    ctx.fillStyle = C.ink; ctx.fillText(label, x + r + 7, y + 0.5);
                }
            }
            ctx.globalAlpha = 1;
        }

        // Interaction: pan, zoom, drag nodes, hover, click to open.
        let drag = null, panning = null, hoverNode = null, moved = false;
        const hit = (sx, sy) => {
            for (let i = data.nodes.length - 1; i >= 0; i--) {
                const n = data.nodes[i], [x, y] = toScreen(n.x, n.y), r = radius(n) * Math.min(view.k, 1.4) + 4;
                if (Math.abs(sx - x) <= r && Math.abs(sy - y) <= r) return n;
            }
            return null;
        };
        const pos = e => { const r = canvas.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; };
        canvas.addEventListener('mousedown', e => {
            const [sx, sy] = pos(e); moved = false; const n = hit(sx, sy);
            if (n) { drag = n; alpha = Math.max(alpha, 0.3); } else panning = { sx, sy, x: view.x, y: view.y };
        });
        window.addEventListener('mousemove', e => {
            const [sx, sy] = pos(e);
            if (drag) { [drag.x, drag.y] = toWorld(sx, sy); drag.vx = drag.vy = 0; moved = true; alpha = Math.max(alpha, 0.3); }
            else if (panning) { view.x = panning.x + sx - panning.sx; view.y = panning.y + sy - panning.sy; moved = true; }
            else {
                hoverNode = hit(sx, sy); canvas.style.cursor = hoverNode ? 'pointer' : '';
                if (hoverNode) {
                    tip.classList.remove('hidden'); tip.style.left = (sx + 16) + 'px'; tip.style.top = (sy + 12) + 'px';
                    tip.querySelector('[data-meta]').textContent = `#${String(hoverNode.id).padStart(3, '0')} · ${hoverNode.type.toUpperCase()}${hoverNode.category ? ' · ' + hoverNode.category.toUpperCase() : ''} · ${hoverNode.deg} conn`;
                    tip.querySelector('[data-title]').textContent = hoverNode.title;
                    tip.querySelector('[data-tags]').textContent = hoverNode.tags.map(t => '#' + t).join(' ');
                } else tip.classList.add('hidden');
            }
        });
        window.addEventListener('mouseup', () => {
            if (drag && !moved) window.location = drag.url;
            drag = null; panning = null;
        });
        canvas.addEventListener('wheel', e => {
            e.preventDefault();
            const [sx, sy] = pos(e), [wx, wy] = toWorld(sx, sy);
            view.k = Math.min(3, Math.max(0.25, view.k * (e.deltaY < 0 ? 1.1 : 0.9)));
            view.x = sx - W / 2 - wx * view.k; view.y = sy - H / 2 - wy * view.k;
        }, { passive: false });
        canvas.addEventListener('mouseleave', () => { hoverNode = null; tip.classList.add('hidden'); });

        window.addEventListener('resize', resize); resize();
        ctx.font = '500 12px "Space Grotesk", sans-serif';
        data.nodes.forEach(n => { const l = n.title.length > 28 ? n.title.slice(0, 27) + '…' : n.title; n.lw = ctx.measureText(l).width + 20; });
        // Pre-settle off-screen so the first paint is already laid out, then animate.
        for (let i = 0; i < 300; i++) tick();
        if (data.nodes.length) { // fit to screen, leaving room for the legend panel
            const xs = data.nodes.map(n => n.x), ys = data.nodes.map(n => n.y);
            const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);
            const availW = W - 340 - 200, availH = H - 140; // legend on the left, labels extend right
            view.k = Math.min(1.3, Math.max(0.35, Math.min(availW / (maxX - minX + 220), availH / (maxY - minY + 80))));
            view.x = 170 - 100 - ((minX + maxX) / 2) * view.k; view.y = -((minY + maxY) / 2) * view.k;
        }
        (function loop() { tick(); draw(); requestAnimationFrame(loop); })();
    })();
    </script>
</x-app-layout>
