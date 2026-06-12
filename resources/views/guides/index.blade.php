@extends('layouts.app', ['title' => 'Panduan Ujian'])

@section('content')


    <div class="sticky top-16 z-10 mb-5 space-y-3">
        {{-- Horizontal Group Navigation with Dropdowns --}}
        <div
            class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 shadow-lg shadow-slate-200/50">
            <div class="flex gap-2 p-2">
                {{-- Siswa Group Dropdown --}}
                <details class="group/siswa relative flex-1" id="siswa-dropdown">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-2xl border border-transparent bg-white px-4 py-3 text-sm font-black uppercase tracking-wider text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700 group-open/siswa:border-teal-300 group-open/siswa:bg-teal-50 group-open/siswa:text-teal-700">
                        <span class="text-base">🎓</span>
                        <span>Siswa</span>
                        <span
                            class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-bold text-teal-700">{{ $studentGuides->count() }}</span>
                        <svg class="size-4 transition-transform group-open/siswa:rotate-180" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path d="M5.5 7.5 10 12l4.5-4.5H5.5Z" />
                        </svg>
                    </summary>
                    <div
                        class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                        @if ($studentGuides->isNotEmpty())
                            <div class="max-h-96 overflow-y-auto">
                                @foreach ($studentGuides as $guide)
                                    <a href="#" data-guide-id="{{ $guide->id }}" data-group="siswa"
                                        @class([
                                            'guide-link group/item relative flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition last:border-b-0',
                                            'bg-teal-50 font-black text-teal-950 guide-active' =>
                                                $selectedGuideId === $guide->id,
                                            'hover:bg-slate-50' => $selectedGuideId !== $guide->id,
                                        ])>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <svg @class([
                                                'size-4',
                                                'text-teal-600' => $selectedGuideId === $guide->id,
                                                'text-slate-400 group-hover/item:text-teal-500' =>
                                                    $selectedGuideId !== $guide->id,
                                            ]) fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-bold text-slate-950">
                                                {{ $guide->title }}
                                            </div>
                                            @if ($guide->toc && count($guide->toc) > 0)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ count($guide->toc) }} bagian
                                                </div>
                                            @endif
                                        </div>
                                        @if ($selectedGuideId === $guide->id)
                                            <div class="absolute left-0 top-0 h-full w-1 bg-teal-600"></div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada panduan siswa
                            </div>
                        @endif
                    </div>
                </details>

                {{-- Panitia Group Dropdown --}}
                <details class="group/panitia relative flex-1" id="panitia-dropdown">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-2xl border border-transparent bg-white px-4 py-3 text-sm font-black uppercase tracking-wider text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 group-open/panitia:border-slate-400 group-open/panitia:bg-slate-50 group-open/panitia:text-slate-900">
                        <span class="text-base">👥</span>
                        <span>Panitia</span>
                        <span
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $committeeGuides->count() }}</span>
                        <svg class="size-4 transition-transform group-open/panitia:rotate-180" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path d="M5.5 7.5 10 12l4.5-4.5H5.5Z" />
                        </svg>
                    </summary>
                    <div
                        class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                        @if ($committeeGuides->isNotEmpty())
                            <div class="max-h-96 overflow-y-auto">
                                @foreach ($committeeGuides as $guide)
                                    <a href="#" data-guide-id="{{ $guide->id }}" data-group="panitia"
                                        @class([
                                            'guide-link group/item relative flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition last:border-b-0',
                                            'bg-slate-100 font-black text-slate-950 guide-active' =>
                                                $selectedGuideId === $guide->id,
                                            'hover:bg-slate-50' => $selectedGuideId !== $guide->id,
                                        ])>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <svg @class([
                                                'size-4',
                                                'text-slate-700' => $selectedGuideId === $guide->id,
                                                'text-slate-400 group-hover/item:text-slate-600' =>
                                                    $selectedGuideId !== $guide->id,
                                            ]) fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-bold text-slate-950">
                                                {{ $guide->title }}
                                            </div>
                                            @if ($guide->toc && count($guide->toc) > 0)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ count($guide->toc) }} bagian
                                                </div>
                                            @endif
                                        </div>
                                        @if ($selectedGuideId === $guide->id)
                                            <div class="absolute left-0 top-0 h-full w-1 bg-slate-700"></div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada panduan panitia
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        </div>

        {{-- Table of Contents - Separate --}}
        <div id="guide-toc-section" @class([
            'rounded-3xl border border-slate-200 bg-white shadow-md',
            'hidden' => !(
                $selectedGuide &&
                $selectedGuide->toc &&
                count($selectedGuide->toc) > 0
            ),
        ])>
            @if ($selectedGuide && $selectedGuide->toc && count($selectedGuide->toc) > 0)
                <details class="group" open>
                    <summary
                        class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 transition hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-7 flex-shrink-0 items-center justify-center rounded-lg bg-teal-100 text-sm">
                                📋
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-950">Daftar Isi</div>
                                <div class="text-xs text-slate-500"><span
                                        id="toc-count">{{ count($selectedGuide->toc) }}</span> bagian</div>
                            </div>
                        </div>
                        <svg class="size-4 text-slate-400 transition-transform group-open:rotate-90" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </summary>
                    <nav id="toc-list" class="border-t border-slate-100 bg-slate-50/50 py-2">
                        @foreach ($selectedGuide->toc as $item)
                            <a href="#{{ $item['id'] }}" @class([
                                'block px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-white hover:text-teal-700',
                                'pl-8' => $item['level'] === 2,
                                'pl-12 text-xs font-semibold' => $item['level'] === 3,
                            ])>{{ $item['title'] }}</a>
                        @endforeach
                    </nav>
                </details>
            @endif
        </div>
    </div>

    <div id="guide-content-wrapper">
        @if ($selectedGuide)
            <article id="guide-article" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <div id="guide-group-badge"
                            class="inline-block rounded-full bg-teal-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-teal-700">
                            {{ $selectedGuide->group }}
                        </div>
                        <h1 id="guide-title" class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                            {{ $selectedGuide->title }}</h1>
                    </div>
                </div>

                <div id="guide-content" class="guide-content">
                    {!! $selectedGuide->content_html !!}
                </div>

                <div id="guide-attachments" class="{{ $selectedGuide->attachments->isNotEmpty() ? '' : 'hidden' }}">
                    @if ($selectedGuide->attachments->isNotEmpty())
                        <div class="mt-8 border-t border-slate-200 pt-5">
                            <h3 class="text-lg font-black text-slate-950">Lampiran</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2" id="attachments-list">
                                @foreach ($selectedGuide->attachments as $attachment)
                                    <a href="{{ $attachment->url }}" target="_blank"
                                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-200 hover:bg-teal-50">
                                        <span
                                            class="block text-sm font-black text-slate-950">{{ $attachment->title }}</span>
                                        <span
                                            class="mt-1 block text-xs font-semibold text-slate-500">{{ $attachment->file_name }}
                                            / {{ $attachment->size_label }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Navigation Buttons --}}
                <div class="mt-8 border-t border-slate-200 pt-5">
                    <div id="guide-navigation" class="flex items-center justify-between gap-4">
                        <button id="prev-guide" type="button"
                            class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:border-slate-200 disabled:hover:bg-white disabled:hover:text-slate-700">
                            <svg class="size-5 transition-transform group-hover:-translate-x-0.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Panduan Sebelumnya</span>
                        </button>
                        <button id="next-guide" type="button"
                            class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:border-slate-200 disabled:hover:bg-white disabled:hover:text-slate-700">
                            <span>Panduan Selanjutnya</span>
                            <svg class="size-5 transition-transform group-hover:translate-x-0.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </article>
        @else
            <div id="guide-placeholder"
                class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
                Pilih panduan dari daftar di atas untuk mulai membaca.
            </div>
        @endif
    </div>

    <script>
        (() => {
            const guideLinks = document.querySelectorAll('.guide-link');
            const contentWrapper = document.getElementById('guide-content-wrapper');
            const tocSection = document.getElementById('guide-toc-section');

            guideLinks.forEach(link => {
                link.addEventListener('click', async (e) => {
                    e.preventDefault();

                    const guideId = link.getAttribute('data-guide-id');
                    const guideGroup = link.getAttribute('data-group');
                    if (!guideId) return;

                    // Update active state
                    document.querySelectorAll('.guide-link').forEach(l => {
                        l.classList.remove('bg-teal-50', 'bg-slate-100', 'font-black',
                            'text-teal-950', 'text-slate-950', 'guide-active');
                        l.classList.add('hover:bg-slate-50');
                    });

                    // Determine group for styling
                    const isSiswaGuide = guideGroup === 'siswa';
                    if (isSiswaGuide) {
                        link.classList.add('bg-teal-50', 'font-black', 'text-teal-950',
                            'guide-active');
                    } else {
                        link.classList.add('bg-slate-100', 'font-black', 'text-slate-950',
                            'guide-active');
                    }
                    link.classList.remove('hover:bg-slate-50');

                    // Close all group dropdowns
                    document.getElementById('siswa-dropdown')?.removeAttribute('open');
                    document.getElementById('panitia-dropdown')?.removeAttribute('open');

                    // Show loading
                    contentWrapper.innerHTML = `
                        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                            <div class="inline-block size-8 animate-spin rounded-full border-4 border-slate-200 border-t-teal-600"></div>
                            <p class="mt-3 text-sm font-semibold text-slate-500">Memuat panduan...</p>
                        </div>
                    `;

                    try {
                        const response = await fetch(`/panduan/${guideId}/ajax`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error('Failed to load guide');

                        const data = await response.json();

                        // Update TOC
                        if (data.toc && data.toc.length > 0) {
                            tocSection.classList.remove('hidden');
                            document.getElementById('toc-count').textContent = data.toc.length;

                            const tocList = document.getElementById('toc-list');
                            if (tocList) {
                                tocList.innerHTML = data.toc.map(item => `
                                    <a href="#${item.id}" class="rounded-lg px-3 py-1.5 text-sm font-bold text-slate-700 transition hover:bg-teal-50 hover:text-teal-700 ${item.level === 3 ? 'ml-3 text-xs' : ''}">
                                        ${item.title}
                                    </a>
                                `).join('');
                            }
                        } else {
                            tocSection.classList.add('hidden');
                        }

                        // Update content
                        const groupBadgeClass = data.group === 'siswa' ?
                            'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-700';

                        let attachmentsHtml = '';
                        if (data.attachments && data.attachments.length > 0) {
                            const attachmentsList = data.attachments.map(att => `
                                <a href="${att.url}" target="_blank" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-200 hover:bg-teal-50">
                                    <span class="block text-sm font-black text-slate-950">${att.title}</span>
                                    <span class="mt-1 block text-xs font-semibold text-slate-500">${att.file_name} / ${att.size_label}</span>
                                </a>
                            `).join('');

                            attachmentsHtml = `
                                <div class="mt-8 border-t border-slate-200 pt-5">
                                    <h3 class="text-lg font-black text-slate-950">Lampiran</h3>
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        ${attachmentsList}
                                    </div>
                                </div>
                            `;
                        }

                        contentWrapper.innerHTML = `
                            <article id="guide-article" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                                <div class="mb-5 flex items-start justify-between gap-4">
                                    <div>
                                        <div class="inline-block rounded-full ${groupBadgeClass} px-3 py-1 text-xs font-black uppercase tracking-wide">
                                            ${data.group}
                                        </div>
                                        <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">${data.title}</h1>
                                    </div>
                                </div>
                                <div class="guide-content">
                                    ${data.content_html}
                                </div>
                                ${attachmentsHtml}
                                <div class="mt-8 border-t border-slate-200 pt-5">
                                    <div id="guide-navigation" class="flex items-center justify-between gap-4">
                                        <button id="prev-guide" type="button" 
                                            class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:border-slate-200 disabled:hover:bg-white disabled:hover:text-slate-700">
                                            <svg class="size-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                            <span>Panduan Sebelumnya</span>
                                        </button>
                                        <button id="next-guide" type="button"
                                            class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:border-slate-200 disabled:hover:bg-white disabled:hover:text-slate-700">
                                            <span>Panduan Selanjutnya</span>
                                            <svg class="size-5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        `;

                        // Update navigation buttons
                        updateNavButtons();

                        // Update URL without reload
                        const url = new URL(window.location);
                        url.searchParams.set('guide', guideId);
                        window.history.pushState({}, '', url);

                        // Scroll to top
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });

                    } catch (error) {
                        console.error('Error loading guide:', error);
                        contentWrapper.innerHTML = `
                            <div class="rounded-3xl border border-rose-200 bg-rose-50 p-10 text-center shadow-sm">
                                <p class="font-semibold text-rose-700">Gagal memuat panduan. Silakan coba lagi.</p>
                            </div>
                        `;
                    }
                });
            });

            // Prev/Next Navigation
            const prevBtn = document.getElementById('prev-guide');
            const nextBtn = document.getElementById('next-guide');

            function getAllGuideIds() {
                const guides = [];
                document.querySelectorAll('.guide-link').forEach(link => {
                    guides.push(link.getAttribute('data-guide-id'));
                });
                return guides;
            }

            function updateNavButtons() {
                const allGuides = getAllGuideIds();
                const activeLink = document.querySelector('.guide-active');

                if (!activeLink || !prevBtn || !nextBtn) return;

                const currentId = activeLink.getAttribute('data-guide-id');
                const currentIndex = allGuides.indexOf(currentId);

                // Update prev button
                if (currentIndex > 0) {
                    prevBtn.disabled = false;
                    prevBtn.setAttribute('data-target-guide', allGuides[currentIndex - 1]);
                } else {
                    prevBtn.disabled = true;
                    prevBtn.removeAttribute('data-target-guide');
                }

                // Update next button
                if (currentIndex < allGuides.length - 1) {
                    nextBtn.disabled = false;
                    nextBtn.setAttribute('data-target-guide', allGuides[currentIndex + 1]);
                } else {
                    nextBtn.disabled = true;
                    nextBtn.removeAttribute('data-target-guide');
                }
            }

            // Initial update
            updateNavButtons();

            // Handle prev/next button clicks
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    const targetId = prevBtn.getAttribute('data-target-guide');
                    if (targetId) {
                        const targetLink = document.querySelector(`[data-guide-id="${targetId}"]`);
                        if (targetLink) targetLink.click();
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    const targetId = nextBtn.getAttribute('data-target-guide');
                    if (targetId) {
                        const targetLink = document.querySelector(`[data-guide-id="${targetId}"]`);
                        if (targetLink) targetLink.click();
                    }
                });
            }

            // Keyboard Shortcuts
            document.addEventListener('keydown', (e) => {
                // Arrow Left: Previous guide
                if (e.key === 'ArrowLeft' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                    const activeElement = document.activeElement;
                    // Don't trigger if user is typing in an input/textarea
                    if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                        if (prevBtn && !prevBtn.disabled) {
                            e.preventDefault();
                            prevBtn.click();
                        }
                    }
                }

                // Arrow Right: Next guide
                if (e.key === 'ArrowRight' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                    const activeElement = document.activeElement;
                    if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                        if (nextBtn && !nextBtn.disabled) {
                            e.preventDefault();
                            nextBtn.click();
                        }
                    }
                }

                // Escape: Toggle filter
                if (e.key === 'Escape') {
                    if (toggleBtn) {
                        e.preventDefault();
                        toggleBtn.click();
                    }
                }
            });
        })();
    </script>
@endsection
