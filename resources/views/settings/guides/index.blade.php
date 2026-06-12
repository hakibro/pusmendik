@extends('layouts.app', ['title' => 'Editor Panduan'])

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Setting</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Editor Panduan</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Kelola satu dokumen Markdown untuk setiap panduan,
                lengkap dengan gambar dan lampiran file impor.</p>
        </div>
        <a href="{{ route('settings.index') }}"
            class="w-fit rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">Kembali
            ke Setting</a>
    </div>

    @if (session('uploaded_image_markdown'))
        <div class="mb-5 rounded-2xl border border-teal-200 bg-teal-50 p-4 text-sm font-semibold text-teal-800">
            Gambar siap dipakai:
            <code id="uploaded-image-snippet"
                class="mt-2 block rounded-xl bg-white p-3 text-slate-800">{{ session('uploaded_image_markdown') }}</code>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[18rem_1fr]">
        <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-4 shadow-sm lg:sticky lg:top-24">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-black text-slate-950">Dokumen</h2>
                <span
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-500">{{ $guides->count() }}</span>
            </div>
            <div class="grid gap-2">
                @forelse($guides as $guide)
                    <a href="{{ route('settings.guides.index', ['guide' => $guide->id]) }}" @class([
                        'rounded-2xl border p-3 transition',
                        'border-teal-200 bg-teal-50' =>
                            $selectedGuide && $selectedGuide->id === $guide->id,
                        'border-slate-200 bg-white hover:bg-slate-50' =>
                            !$selectedGuide || $selectedGuide->id !== $guide->id,
                    ])>
                        <span class="block text-sm font-black text-slate-950">{{ $guide->title }}</span>
                        <span class="mt-1 flex items-center gap-2 text-xs font-bold text-slate-500">
                            <span>{{ ucfirst($guide->group) }}</span>
                            <span>/</span>
                            <span>{{ $guide->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </span>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500">
                        Belum ada panduan.</div>
                @endforelse
            </div>

            <form method="post" action="{{ route('settings.guides.store') }}" class="mt-5 border-t border-slate-200 pt-4">
                @csrf
                <input type="hidden" name="group" value="panitia">
                <input type="hidden" name="sort_order" value="{{ ((int) $guides->max('sort_order')) + 10 }}">
                <input type="hidden" name="content_md" value="{{ $suggestedContent }}">
                <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Panduan Baru
                    <input name="title" required placeholder="Panduan Import Soal"
                        class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                </label>
                <label class="mt-3 grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Slug
                    <input name="slug" required placeholder="import-soal"
                        class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                </label>
                <button
                    class="mt-3 w-full rounded-2xl bg-teal-600 px-4 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Tambah
                    Panduan</button>
            </form>
        </aside>

        <div class="grid gap-5">
            @if ($selectedGuide)
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <form method="post" action="{{ route('settings.guides.update', $selectedGuide->id) }}"
                        class="grid gap-4">
                        @csrf
                        @method('put')
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Judul
                                <input name="title" value="{{ old('title', $selectedGuide->title) }}" required
                                    class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                            </label>
                            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Slug
                                <input name="slug" value="{{ old('slug', $selectedGuide->slug) }}" required
                                    class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Group
                                <select name="group" data-no-auto-submit
                                    class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                                    <option value="siswa" @selected(old('group', $selectedGuide->group) === 'siswa')>Siswa</option>
                                    <option value="panitia" @selected(old('group', $selectedGuide->group) === 'panitia')>Panitia</option>
                                </select>
                            </label>
                            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Urutan
                                <input type="number" min="0" name="sort_order"
                                    value="{{ old('sort_order', $selectedGuide->sort_order) }}" required
                                    class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                            </label>
                            <label
                                class="flex items-center gap-3 self-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $selectedGuide->is_active))
                                    class="size-5 rounded border-slate-300 text-teal-600">
                                Aktif
                            </label>
                        </div>
                        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Konten
                            Markdown
                            <textarea id="guide-markdown" name="content_md"
                                class="min-h-[34rem] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('content_md', $selectedGuide->content_md) }}</textarea>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                class="rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Simpan
                                Panduan</button>
                        </div>
                    </form>
                    <form method="post" action="{{ route('settings.guides.delete', $selectedGuide->id) }}" class="mt-3"
                        onsubmit="return confirm('Hapus panduan ini beserta lampirannya?')">
                        @csrf
                        @method('delete')
                        <button
                            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 transition hover:bg-rose-100">Hapus
                            Panduan</button>
                    </form>
                </section>

                <section class="grid gap-5 xl:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-xl font-black text-slate-950">Upload Gambar</h2>
                        <p class="mt-1 text-sm text-slate-500">Format: jpg, jpeg, png, webp. Maksimal 2 MB.</p>
                        <form method="post" action="{{ route('settings.guides.upload-image') }}"
                            enctype="multipart/form-data" class="mt-4 grid gap-3">
                            @csrf
                            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Alt Text
                                <input id="guide-image-alt" name="alt" placeholder="Halaman Import"
                                    class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                            </label>
                            <input id="guide-image-file" type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                                required
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            <div id="guide-image-paste-status" class="text-xs font-semibold text-slate-400">Paste gambar
                                langsung di kolom Alt Text.</div>
                            <button
                                class="w-fit rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-teal-700">Upload
                                Gambar</button>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-xl font-black text-slate-950">Lampiran Dokumen</h2>
                        <p class="mt-1 text-sm text-slate-500">Format: doc, docx, xls, xlsx, pdf. Maksimal 10 MB.</p>
                        <form method="post"
                            action="{{ route('settings.guides.attachments.store', $selectedGuide->id) }}"
                            enctype="multipart/form-data" class="mt-4 grid gap-3">
                            @csrf
                            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Judul
                                Lampiran
                                <input id="guide-attachment-title" name="title" required
                                    placeholder="Template Import Soal"
                                    class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                            </label>
                            <input id="guide-attachment-file" type="file" name="attachment"
                                accept=".doc,.docx,.xls,.xlsx,.pdf" required
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            <div id="guide-attachment-paste-status" class="text-xs font-semibold text-slate-400">Paste
                                dokumen langsung di kolom Judul Lampiran.</div>
                            <button
                                class="w-fit rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-teal-700">Upload
                                Lampiran</button>
                        </form>
                        <div class="mt-4 grid gap-2">
                            @forelse($attachments as $attachment)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                                        <div>
                                            <div class="text-sm font-black text-slate-950">{{ $attachment->title }}</div>
                                            <div class="mt-1 text-xs font-semibold text-slate-500">
                                                {{ $attachment->file_name }} / {{ $attachment->size_label }}</div>
                                        </div>
                                        <form method="post"
                                            action="{{ route('settings.guides.attachments.delete', $attachment->id) }}"
                                            onsubmit="return confirm('Hapus lampiran ini?')">
                                            @csrf
                                            @method('delete')
                                            <button
                                                class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-black text-rose-700">Hapus</button>
                                        </form>
                                    </div>
                                    <code
                                        class="mt-2 block overflow-x-auto rounded-xl bg-white p-2 text-xs text-slate-700">{{ $attachment->markdown }}</code>
                                </div>
                            @empty
                                <div
                                    class="rounded-2xl border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500">
                                    Belum ada lampiran.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">Preview</h2>
                            <p class="mt-1 text-sm text-slate-500">Preview memakai renderer yang sama dengan halaman
                                publik.</p>
                        </div>
                        <span id="preview-status" class="text-xs font-bold text-slate-400">Siap</span>
                    </div>
                    <div id="guide-preview" class="guide-content rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        {!! $renderedPreview !!}
                    </div>
                </section>
            @else
                <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
                    Tambahkan panduan pertama untuk mulai menulis.</div>
            @endif
        </div>
    </div>

    <script>
        (() => {
            const textarea = document.getElementById('guide-markdown');
            const preview = document.getElementById('guide-preview');
            const status = document.getElementById('preview-status');
            if (!textarea || !preview || !status) return;

            let timer;
            const render = () => {
                status.textContent = 'Memuat preview...';
                clearTimeout(timer);
                timer = setTimeout(async () => {
                    const response = await fetch(`{{ route('settings.guides.preview') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': `{{ csrf_token() }}`,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            content_md: textarea.value
                        }),
                    }).catch(() => null);

                    if (!response || !response.ok) {
                        status.textContent = 'Preview gagal';
                        return;
                    }

                    const data = await response.json();
                    preview.innerHTML = data.html || '';
                    status.textContent = 'Preview diperbarui';
                }, 450);
            };

            textarea.addEventListener('input', render);
        })();

        (() => {
            const bindPasteFile = ({
                textInputId,
                fileInputId,
                statusId,
                allowed,
                label
            }) => {
                const textInput = document.getElementById(textInputId);
                const fileInput = document.getElementById(fileInputId);
                const status = document.getElementById(statusId);
                if (!textInput || !fileInput || !status) return;

                const allowedExt = allowed.map((item) => item.toLowerCase());
                const fileLabel = (file) => (file.name || label).replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ')
                    .trim();
                const isAllowed = (file) => {
                    const extension = (file.name || '').split('.').pop()?.toLowerCase();
                    return allowedExt.includes(extension) || allowedExt.some((ext) => file.type.toLowerCase()
                        .includes(ext));
                };

                textInput.addEventListener('paste', (event) => {
                    const files = Array.from(event.clipboardData?.files || []);
                    const file = files.find(isAllowed);
                    if (!file) return;

                    event.preventDefault();
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    fileInput.files = transfer.files;

                    if (!textInput.value.trim()) {
                        textInput.value = fileLabel(file);
                    }

                    status.textContent = `${label} siap diupload: ${file.name || 'file dari clipboard'}`;
                    status.classList.remove('text-slate-400', 'text-rose-600');
                    status.classList.add('text-teal-700');
                });
            };

            bindPasteFile({
                textInputId: 'guide-image-alt',
                fileInputId: 'guide-image-file',
                statusId: 'guide-image-paste-status',
                allowed: ['jpg', 'jpeg', 'png', 'webp'],
                label: 'Gambar',
            });

            bindPasteFile({
                textInputId: 'guide-attachment-title',
                fileInputId: 'guide-attachment-file',
                statusId: 'guide-attachment-paste-status',
                allowed: ['doc', 'docx', 'xls', 'xlsx', 'pdf'],
                label: 'Lampiran',
            });
        })();

        (() => {
            const textarea = document.getElementById('guide-markdown');
            if (!textarea) return;

            let uploadingCount = 0;

            const insertAtCursor = (text) => {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const before = textarea.value.substring(0, start);
                const after = textarea.value.substring(end);
                textarea.value = before + text + after;
                textarea.selectionStart = textarea.selectionEnd = start + text.length;
                textarea.dispatchEvent(new Event('input'));
                textarea.focus();
            };

            const uploadImage = async (file) => {
                const formData = new FormData();
                formData.append('image', file);
                formData.append('alt', (file.name || 'image').replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ')
                    .trim());

                try {
                    const response = await fetch(`{{ route('settings.guides.upload-image-ajax') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': `{{ csrf_token() }}`,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('Upload failed');
                    }

                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.error('Upload error:', error);
                    return null;
                }
            };

            textarea.addEventListener('paste', async (event) => {
                const items = Array.from(event.clipboardData?.items || []);
                const imageItems = items.filter((item) => item.type.startsWith('image/'));

                if (imageItems.length === 0) return;

                event.preventDefault();

                for (const item of imageItems) {
                    const file = item.getAsFile();
                    if (!file) continue;

                    uploadingCount++;
                    const placeholder = `\n[Mengupload gambar...]\n`;
                    insertAtCursor(placeholder);

                    const result = await uploadImage(file);

                    if (result && result.markdown) {
                        textarea.value = textarea.value.replace(placeholder, `\n${result.markdown}\n`);
                    } else {
                        textarea.value = textarea.value.replace(placeholder, `\n[Upload gambar gagal]\n`);
                    }

                    textarea.dispatchEvent(new Event('input'));
                    uploadingCount--;
                }
            });

            textarea.addEventListener('dragover', (event) => {
                event.preventDefault();
            });

            textarea.addEventListener('drop', async (event) => {
                const files = Array.from(event.dataTransfer?.files || []);
                const imageFiles = files.filter((file) => file.type.startsWith('image/'));

                if (imageFiles.length === 0) return;

                event.preventDefault();

                for (const file of imageFiles) {
                    uploadingCount++;
                    const placeholder = `\n[Mengupload gambar...]\n`;
                    insertAtCursor(placeholder);

                    const result = await uploadImage(file);

                    if (result && result.markdown) {
                        textarea.value = textarea.value.replace(placeholder, `\n${result.markdown}\n`);
                    } else {
                        textarea.value = textarea.value.replace(placeholder, `\n[Upload gambar gagal]\n`);
                    }

                    textarea.dispatchEvent(new Event('input'));
                    uploadingCount--;
                }
            });
        })();
    </script>
@endsection
