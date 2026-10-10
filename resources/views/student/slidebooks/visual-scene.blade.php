@php
    $sceneId = $scene['id'] ?? 'learning';
    $sceneLabel = $scene['label'] ?? 'Ilustrasi konsep pembelajaran';
    $arrowId = 'scene-arrow-' . ($slide->id ?? uniqid());
@endphp

<figure class="learning-scene scene-{{ $sceneId }}" data-scene="{{ $sceneId }}">
    <figcaption class="sr-only">{{ $sceneLabel }}</figcaption>
    <svg viewBox="0 0 720 320" role="img" aria-label="{{ $sceneLabel }}" preserveAspectRatio="xMidYMid meet">
        <defs>
            <marker id="{{ $arrowId }}" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto" markerUnits="strokeWidth">
                <path d="M0,0 L8,4 L0,8 z" class="scene-arrow-head" />
            </marker>
        </defs>

        @switch($sceneId)
            @case('web-flow')
                <g class="scene-connector">
                    <path d="M170 155 C225 155 230 155 275 155" marker-end="url(#{{ $arrowId }})" />
                    <path d="M395 155 C445 155 450 155 500 155" marker-end="url(#{{ $arrowId }})" />
                    <path d="M575 205 C575 235 575 240 575 260" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(60 95)">
                    <rect class="scene-node" x="0" y="0" width="110" height="120" rx="24" />
                    <circle class="scene-accent-fill" cx="55" cy="34" r="17" />
                    <path class="scene-icon-line" d="M31 90c7-24 41-24 48 0M48 34h14" />
                    <text class="scene-label" x="55" y="108" text-anchor="middle">Siswa</text>
                </g>
                <g transform="translate(275 85)">
                    <rect class="scene-node scene-node-emphasis" x="0" y="0" width="120" height="140" rx="24" />
                    <rect class="scene-screen" x="22" y="24" width="76" height="52" rx="8" />
                    <circle class="scene-dot" cx="33" cy="35" r="3" />
                    <circle class="scene-dot" cx="43" cy="35" r="3" />
                    <path class="scene-icon-line" d="M44 100h32M60 76v24" />
                    <text class="scene-label" x="60" y="126" text-anchor="middle">Browser</text>
                </g>
                <g transform="translate(500 75)">
                    <rect class="scene-node" x="0" y="0" width="150" height="130" rx="24" />
                    <rect class="scene-screen" x="28" y="24" width="94" height="26" rx="7" />
                    <rect class="scene-screen" x="28" y="58" width="94" height="26" rx="7" />
                    <circle class="scene-dot" cx="42" cy="37" r="4" />
                    <circle class="scene-dot" cx="42" cy="71" r="4" />
                    <text class="scene-label" x="75" y="111" text-anchor="middle">Server</text>
                </g>
                <g transform="translate(520 255)">
                    <ellipse class="scene-node" cx="55" cy="15" rx="55" ry="15" />
                    <path class="scene-node" d="M0 15v32c0 8 25 15 55 15s55-7 55-15V15" />
                    <path class="scene-icon-line" d="M0 31c0 8 25 15 55 15s55-7 55-15" />
                    <text class="scene-label" x="55" y="87" text-anchor="middle">Data</text>
                </g>
                @break

            @case('database')
                <g class="scene-connector">
                    <path d="M185 160 H300" marker-end="url(#{{ $arrowId }})" />
                    <path d="M420 160 H515" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(65 95)">
                    <rect class="scene-node" width="120" height="130" rx="24" />
                    <rect class="scene-screen" x="22" y="22" width="76" height="58" rx="8" />
                    <path class="scene-icon-line" d="M40 98h40M60 80v18" />
                    <text class="scene-label" x="60" y="119" text-anchor="middle">Aplikasi</text>
                </g>
                <g transform="translate(300 105)">
                    <rect class="scene-node scene-node-emphasis" width="120" height="110" rx="55" />
                    <path class="scene-icon-line" d="M36 42h48M36 56h48M36 70h48" />
                    <text class="scene-label" x="60" y="94" text-anchor="middle">API</text>
                </g>
                <g transform="translate(515 80)">
                    <ellipse class="scene-node scene-node-emphasis" cx="70" cy="24" rx="70" ry="24" />
                    <path class="scene-node scene-node-emphasis" d="M0 24v112c0 13 31 24 70 24s70-11 70-24V24" />
                    <path class="scene-icon-line" d="M0 60c0 13 31 24 70 24s70-11 70-24M0 98c0 13 31 24 70 24s70-11 70-24" />
                    <text class="scene-label" x="70" y="148" text-anchor="middle">Database</text>
                </g>
                @break

            @case('security')
                <g class="scene-connector">
                    <path d="M180 160 H295" marker-end="url(#{{ $arrowId }})" />
                    <path d="M425 160 H535" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(65 100)">
                    <rect class="scene-node" width="115" height="120" rx="24" />
                    <circle class="scene-accent-fill" cx="57" cy="34" r="17" />
                    <path class="scene-icon-line" d="M32 88c8-26 42-26 50 0" />
                    <text class="scene-label" x="57" y="108" text-anchor="middle">Pengguna</text>
                </g>
                <g transform="translate(295 70)">
                    <path class="scene-shield" d="M65 8 120 28v54c0 46-33 72-55 82C43 154 10 128 10 82V28Z" />
                    <path class="scene-icon-line scene-icon-heavy" d="m39 82 18 18 35-43" />
                    <text class="scene-label" x="65" y="190" text-anchor="middle">Proteksi</text>
                </g>
                <g transform="translate(535 100)">
                    <rect class="scene-node" width="120" height="120" rx="24" />
                    <rect class="scene-screen" x="22" y="22" width="76" height="54" rx="8" />
                    <circle class="scene-dot" cx="60" cy="49" r="9" />
                    <path class="scene-icon-line" d="M60 58v10" />
                    <text class="scene-label" x="60" y="108" text-anchor="middle">Aplikasi</text>
                </g>
                @break

            @case('network')
                <g class="scene-connector">
                    <path d="M140 205 C205 205 225 165 290 165" marker-end="url(#{{ $arrowId }})" />
                    <path d="M140 105 C205 105 225 145 290 145" marker-end="url(#{{ $arrowId }})" />
                    <path d="M420 155 H540" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(55 55)">
                    <rect class="scene-node" width="85" height="105" rx="20" />
                    <rect class="scene-screen" x="18" y="20" width="49" height="49" rx="7" />
                    <text class="scene-label" x="42" y="92" text-anchor="middle">Laptop</text>
                </g>
                <g transform="translate(65 185)">
                    <rect class="scene-node" width="65" height="105" rx="18" />
                    <rect class="scene-screen" x="12" y="15" width="41" height="60" rx="7" />
                    <text class="scene-label" x="32" y="94" text-anchor="middle">HP</text>
                </g>
                <g transform="translate(290 95)">
                    <rect class="scene-node scene-node-emphasis" width="130" height="120" rx="30" />
                    <path class="scene-icon-line" d="M37 56c16-16 40-16 56 0M47 67c10-10 26-10 36 0M61 79h8" />
                    <text class="scene-label" x="65" y="105" text-anchor="middle">Router</text>
                </g>
                <g transform="translate(540 95)">
                    <rect class="scene-node" width="125" height="120" rx="24" />
                    <rect class="scene-screen" x="22" y="23" width="81" height="28" rx="6" />
                    <rect class="scene-screen" x="22" y="59" width="81" height="28" rx="6" />
                    <text class="scene-label" x="62" y="107" text-anchor="middle">Server</text>
                </g>
                @break

            @case('ai')
                <g class="scene-connector">
                    <path d="M180 160 H285" marker-end="url(#{{ $arrowId }})" />
                    <path d="M435 160 H540" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(60 100)">
                    <rect class="scene-node" width="120" height="120" rx="24" />
                    <path class="scene-icon-line" d="M27 40h66M27 58h52M27 76h38" />
                    <text class="scene-label" x="60" y="105" text-anchor="middle">Data</text>
                </g>
                <g transform="translate(285 65)">
                    <rect class="scene-node scene-node-emphasis" width="150" height="190" rx="55" />
                    <circle class="scene-accent-fill" cx="75" cy="65" r="34" />
                    <path class="scene-icon-line" d="M57 62h36M60 77h30M48 121h54M54 137h42" />
                    <text class="scene-label" x="75" y="170" text-anchor="middle">AI Model</text>
                </g>
                <g transform="translate(540 100)">
                    <rect class="scene-node" width="120" height="120" rx="24" />
                    <path class="scene-icon-line scene-icon-heavy" d="m30 64 20 20 40-48" />
                    <text class="scene-label" x="60" y="105" text-anchor="middle">Output</text>
                </g>
                @break

            @case('code')
                <g class="scene-connector">
                    <path d="M235 160 H330" marker-end="url(#{{ $arrowId }})" />
                    <path d="M440 160 H525" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(50 85)">
                    <rect class="scene-node" width="185" height="150" rx="24" />
                    <path class="scene-icon-line" d="m58 62-24 18 24 18M127 62l24 18-24 18M105 46 80 114" />
                    <text class="scene-label" x="92" y="135" text-anchor="middle">Kode</text>
                </g>
                <g transform="translate(330 105)">
                    <circle class="scene-node scene-node-emphasis" cx="55" cy="55" r="55" />
                    <path class="scene-icon-line" d="M55 26v18M55 66v18M26 55h18M66 55h18M35 35l13 13M62 62l13 13M75 35 62 48M48 62 35 75" />
                    <circle class="scene-accent-fill" cx="55" cy="55" r="13" />
                </g>
                <g transform="translate(525 85)">
                    <rect class="scene-node" width="150" height="150" rx="24" />
                    <rect class="scene-screen" x="24" y="25" width="102" height="70" rx="8" />
                    <path class="scene-icon-line" d="M50 120h50M75 95v25" />
                    <text class="scene-label" x="75" y="140" text-anchor="middle">Hasil</text>
                </g>
                @break

            @default
                <g class="scene-connector">
                    <path d="M190 160 H285" marker-end="url(#{{ $arrowId }})" />
                    <path d="M430 160 H525" marker-end="url(#{{ $arrowId }})" />
                </g>
                <g transform="translate(65 95)">
                    <rect class="scene-node" width="125" height="130" rx="28" />
                    <circle class="scene-accent-fill" cx="62" cy="38" r="18" />
                    <path class="scene-icon-line" d="M34 96c9-28 47-28 56 0" />
                    <text class="scene-label" x="62" y="118" text-anchor="middle">Siswa</text>
                </g>
                <g transform="translate(285 90)">
                    <rect class="scene-node scene-node-emphasis" width="145" height="140" rx="30" />
                    <path class="scene-icon-line" d="M30 43c27-12 58-12 85 0v65c-27-12-58-12-85 0Zm42 0v65" />
                    <text class="scene-label" x="72" y="128" text-anchor="middle">Materi</text>
                </g>
                <g transform="translate(525 95)">
                    <rect class="scene-node" width="130" height="130" rx="28" />
                    <circle class="scene-screen" cx="65" cy="50" r="28" />
                    <path class="scene-icon-line scene-icon-heavy" d="m50 50 11 11 20-24" />
                    <text class="scene-label" x="65" y="111" text-anchor="middle">Latihan</text>
                </g>
        @endswitch
    </svg>
</figure>
