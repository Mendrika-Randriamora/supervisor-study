@extends('layouts.app')

@section('title', 'Correction de la soumission')
@section('topbar-title', 'Correction')

@section('content')
    @php
        $exercise = $submission->exercise;
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                <h1 class="page-title mb-0">Correction</h1>
                <x-status-badge :status="$submission->status" />
                @if ($submission->is_late)<span class="badge text-bg-warning">Rendue en retard</span>@endif
            </div>
            <p class="page-subtitle mb-0">{{ $exercise->title }} — {{ $exercise->theme?->subject?->name }} / {{ $exercise->theme?->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('supervisor.submissions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            <a href="{{ route('supervisor.exercises.show', $exercise) }}" class="btn btn-outline-primary btn-sm">Voir l’exercice</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Copie de {{ $submission->student?->name }}</span>
                    <span class="text-muted text-tiny">Version {{ $submission->version }} — {{ $submission->submitted_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="card-body">
                    @if ($exercise->type === \App\Enums\ExerciseType::Text)
                        <div class="soft-panel no-copy" data-no-copy>
                            <div class="small text-muted mb-2"><i class="bi bi-lock me-1"></i>Réponse textuelle de l’étudiant — sélection désactivée</div>
                            <div style="white-space: pre-wrap">{{ $submission->text_answer }}</div>
                        </div>
                    @elseif ($submission->files->isEmpty())
                        <p class="text-muted small mb-0">Aucune image n’a été jointe à cette soumission.</p>
                    @else
                        <div class="row g-2">
                            @foreach ($submission->files as $file)
                                @php
                                    $ownAnnotation = $file->annotations->firstWhere('created_by', auth()->id());
                                    $ownAnnotationData = is_array($ownAnnotation?->data) ? $ownAnnotation->data : [];
                                    $annotationData = [
                                        'paths' => $ownAnnotationData['paths'] ?? [],
                                        'rects' => $ownAnnotationData['rects'] ?? [],
                                        'comments' => $ownAnnotationData['comments'] ?? [],
                                    ];
                                @endphp
                                <div class="col-6 col-md-4">
                                    <div class="border rounded p-1 h-100 d-flex flex-column">
                                        <a href="{{ route('files.submission', $file) }}" target="_blank" class="d-block">
                                            <img src="{{ route('files.submission', $file) }}" alt="Copie {{ $loop->iteration }}" class="img-fluid w-100" style="height:130px;object-fit:cover">
                                        </a>
                                        <div class="d-flex gap-1 mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#annotateModal-{{ $file->id }}">
                                                <i class="bi bi-pencil-square me-1"></i>Annoter
                                            </button>
                                            <a href="{{ route('files.submission', $file) }}" target="_blank" class="btn btn-sm btn-outline-secondary" aria-label="Ouvrir en plein écran"><i class="bi bi-arrows-fullscreen"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="annotateModal-{{ $file->id }}" tabindex="-1" aria-labelledby="annotateModalLabel-{{ $file->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" data-annotation-modal>
                                            <div class="modal-header py-2">
                                                <h2 class="modal-title fs-6" id="annotateModalLabel-{{ $file->id }}">Annoter — {{ $file->original_name }}</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="annotation-toolbar mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary annotation-tool active" data-annotation-tool="draw"><i class="bi bi-pencil me-1"></i>Dessin</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary annotation-tool" data-annotation-tool="highlight"><i class="bi bi-highlighter me-1"></i>Surligner</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary annotation-tool" data-annotation-tool="comment"><i class="bi bi-chat-dots me-1"></i>Commentaire</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-annotation-clear><i class="bi bi-eraser me-1"></i>Effacer</button>
                                                    <a href="{{ route('files.submission', $file) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrows-fullscreen"></i></a>
                                                </div>

                                                <div class="annotation-stage" data-annotation-stage>
                                                    <img src="{{ route('files.submission', $file) }}" alt="Copie à annoter">
                                                    <canvas aria-label="Calque d’annotation"></canvas>
                                                    <div class="annotation-pins"></div>
                                                </div>
                                                <script type="application/json" class="annotation-data">@json($annotationData)</script>

                                                <p class="form-text mt-2">Les annotations sont enregistrées dans un calque séparé : l’image originale n’est jamais modifiée.</p>

                                                <form method="POST" action="{{ route('supervisor.submissions.annotate', [$submission, $file]) }}" data-annotation-form>
                                                    @csrf
                                                    <input type="hidden" name="data" value="[]" data-annotation-input>
                                                    <div class="mb-2">
                                                        <label for="annotation-comment-{{ $file->id }}" class="form-label">Commentaire de correction (facultatif)</label>
                                                        <textarea class="form-control" id="annotation-comment-{{ $file->id }}" name="comment" rows="2">{{ $file->annotations->firstWhere('created_by', auth()->id())?->comment }}</textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Enregistrer les annotations</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Historique des versions</div>
                <div class="card-body p-0">
                    @foreach ($versions as $version)
                        <a href="{{ route('supervisor.submissions.show', $version) }}" class="d-flex justify-content-between gap-2 px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }} {{ $version->id === $submission->id ? 'bg-primary-soft' : '' }}">
                            <div>
                                <div class="small fw-semibold">Version {{ $version->version }} @if ($version->id === $submission->id)<span class="badge text-bg-primary">Actuelle</span>@endif</div>
                                <div class="meta-line"><span>{{ $version->submitted_at->format('d/m/Y H:i') }}</span><span><x-status-badge :status="$version->status" /></span></div>
                            </div>
                            <div class="text-end small">
                                @if ($version->latestGrade)
                                    <strong>{{ $version->latestGrade->score }} / {{ $version->latestGrade->max_points }}</strong>
                                @else
                                    <span class="text-muted">Non corrigée</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Attribuer une note</div>
                <div class="card-body">
                    @if ($submission->latestGrade)
                        <div class="alert alert-success py-2 small mb-3">
                            Dernière note : <strong>{{ $submission->latestGrade->score }} / {{ $submission->latestGrade->max_points }}</strong>
                            ({{ number_format($submission->latestGrade->convertedTo20(), 2, ',', ' ') }}/20)
                        </div>
                    @endif

                    <form method="POST" action="{{ route('supervisor.submissions.grade', $submission) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="score" class="form-label">Note obtenue <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.25" min="0" max="{{ $exercise->max_points }}" id="score" name="score"
                                       value="{{ old('score', $submission->latestGrade?->score) }}" class="form-control @error('score') is-invalid @enderror" required>
                                <span class="input-group-text">/ {{ $exercise->max_points }}</span>
                                @error('score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Le barème est libre ; la conversion sur 20 est automatique.</div>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Commentaire général</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Points forts, erreurs, conseils…">{{ old('comment', $submission->latestGrade?->comment) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle me-1"></i>Enregistrer la correction</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Demander une nouvelle réponse</div>
                <div class="card-body">
                    <p class="small text-muted">L’étudiant pourra envoyer une nouvelle version. Les versions précédentes restent conservées.</p>
                    <form method="POST" action="{{ route('supervisor.submissions.revision', $submission) }}">
                        @csrf
                        <div class="mb-2">
                            <label for="revision-comment" class="form-label">Explication <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="revision-comment" name="comment" rows="3" required placeholder="Ce qui doit être repris…"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100" data-confirm="Demander une nouvelle réponse à l’étudiant ?">
                            <i class="bi bi-arrow-repeat me-1"></i>Demander une nouvelle réponse
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Anciennes corrections</div>
                <div class="card-body">
                    @forelse ($submission->grades as $grade)
                        <div class="py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex justify-content-between">
                                <span class="small fw-semibold">{{ $grade->score }} / {{ $grade->max_points }}</span>
                                <span class="text-muted text-tiny">{{ $grade->graded_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="text-muted text-tiny">Par {{ $grade->grader?->name ?? 'Superviseur supprimé' }}</div>
                            @if ($grade->comment)
                                <div class="small mt-1">{{ $grade->comment }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Aucune correction enregistrée.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const colors = { draw: '#b42318', highlight: 'rgba(250, 204, 21, 0.35)' };

            document.querySelectorAll('[data-annotation-modal]').forEach((modal) => {
                const stage = modal.querySelector('[data-annotation-stage]');
                const image = stage?.querySelector('img');
                const canvas = stage?.querySelector('canvas');
                const pins = stage?.querySelector('.annotation-pins');
                const dataElement = modal.querySelector('.annotation-data');
                const input = modal.querySelector('[data-annotation-input]');
                const form = modal.querySelector('[data-annotation-form]');
                if (!stage || !image || !canvas || !dataElement || !input || !form) return;

                const data = JSON.parse(dataElement.textContent || '{"paths":[],"rects":[],"comments":[]}');
                data.paths ||= [];
                data.rects ||= [];
                data.comments ||= [];

                let tool = 'draw';
                let drawing = false;
                let currentPath = null;
                let currentRect = null;
                let startPoint = null;
                let scaleX = 1;
                let scaleY = 1;

                const context = canvas.getContext('2d');

                const resize = () => {
                    const rect = image.getBoundingClientRect();
                    if (!rect.width || !rect.height) return;
                    canvas.width = Math.round(rect.width * (window.devicePixelRatio || 1));
                    canvas.height = Math.round(rect.height * (window.devicePixelRatio || 1));
                    canvas.style.width = rect.width + 'px';
                    canvas.style.height = rect.height + 'px';
                    scaleX = canvas.width / rect.width;
                    scaleY = canvas.height / rect.height;
                    render();
                };

                const drawPath = (path) => {
                    if (!path.points || path.points.length < 2) return;
                    context.beginPath();
                    context.strokeStyle = path.color || colors.draw;
                    context.lineWidth = (path.width || 2) * scaleX;
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    path.points.forEach((point, index) => {
                        const x = point[0] * canvas.width;
                        const y = point[1] * canvas.height;
                        if (index === 0) context.moveTo(x, y);
                        else context.lineTo(x, y);
                    });
                    context.stroke();
                };

                const drawRect = (rect) => {
                    context.fillStyle = rect.color || colors.highlight;
                    context.fillRect(rect.x * canvas.width, rect.y * canvas.height, rect.w * canvas.width, rect.h * canvas.height);
                    context.strokeStyle = 'rgba(180, 130, 10, .8)';
                    context.lineWidth = 1 * scaleX;
                    context.strokeRect(rect.x * canvas.width, rect.y * canvas.height, rect.w * canvas.width, rect.h * canvas.height);
                };

                const renderPins = () => {
                    pins.innerHTML = '';
                    data.comments.forEach((comment) => {
                        const pin = document.createElement('span');
                        pin.className = 'annotation-comment-pin';
                        pin.style.left = (comment.x * 100) + '%';
                        pin.style.top = (comment.y * 100) + '%';
                        pin.title = comment.text || '';
                        pin.innerHTML = '<i class="bi bi-chat-fill"></i>';
                        pins.appendChild(pin);
                    });
                };

                const render = () => {
                    context.clearRect(0, 0, canvas.width, canvas.height);
                    data.rects.forEach(drawRect);
                    data.paths.forEach(drawPath);
                    renderPins();
                };

                const pointFromEvent = (event) => {
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width)),
                        y: Math.min(1, Math.max(0, (event.clientY - rect.top) / rect.height)),
                    };
                };

                canvas.addEventListener('pointerdown', (event) => {
                    event.preventDefault();
                    const point = pointFromEvent(event);
                    canvas.setPointerCapture(event.pointerId);

                    if (tool === 'comment') {
                        const text = window.prompt('Commentaire visuel :');
                        if (text && text.trim()) {
                            data.comments.push({ x: point.x, y: point.y, text: text.trim() });
                            render();
                        }
                        return;
                    }

                    drawing = true;
                    startPoint = point;

                    if (tool === 'draw') {
                        currentPath = { color: colors.draw, width: 2, points: [[point.x, point.y]] };
                    } else {
                        currentRect = { x: point.x, y: point.y, w: 0, h: 0, color: colors.highlight };
                    }
                });

                canvas.addEventListener('pointermove', (event) => {
                    if (!drawing) return;
                    event.preventDefault();
                    const point = pointFromEvent(event);

                    if (tool === 'draw' && currentPath) {
                        currentPath.points.push([point.x, point.y]);
                        context.strokeStyle = currentPath.color;
                        context.lineWidth = (currentPath.width || 2) * scaleX;
                        context.lineCap = 'round';
                        context.beginPath();
                        const points = currentPath.points;
                        const previous = points[points.length - 2];
                        context.moveTo(previous[0] * canvas.width, previous[1] * canvas.height);
                        context.lineTo(point.x * canvas.width, point.y * canvas.height);
                        context.stroke();
                    } else if (tool === 'highlight' && currentRect && startPoint) {
                        currentRect.x = Math.min(startPoint.x, point.x);
                        currentRect.y = Math.min(startPoint.y, point.y);
                        currentRect.w = Math.abs(point.x - startPoint.x);
                        currentRect.h = Math.abs(point.y - startPoint.y);
                        render();
                        drawRect(currentRect);
                    }
                });

                const finish = () => {
                    if (!drawing) return;
                    drawing = false;

                    if (tool === 'draw' && currentPath) {
                        if (currentPath.points.length > 1) data.paths.push(currentPath);
                        currentPath = null;
                    } else if (tool === 'highlight' && currentRect) {
                        if (currentRect.w > 0.01 && currentRect.h > 0.01) data.rects.push(currentRect);
                        currentRect = null;
                    }

                    startPoint = null;
                    render();
                };

                canvas.addEventListener('pointerup', finish);
                canvas.addEventListener('pointercancel', finish);
                canvas.addEventListener('pointerleave', (event) => {
                    if (drawing && event.buttons === 0) finish();
                });

                modal.querySelectorAll('[data-annotation-tool]').forEach((button) => {
                    button.addEventListener('click', () => {
                        tool = button.dataset.annotationTool;
                        modal.querySelectorAll('[data-annotation-tool]').forEach((item) => item.classList.toggle('active', item === button));
                    });
                });

                modal.querySelector('[data-annotation-clear]')?.addEventListener('click', () => {
                    if (!window.confirm('Effacer toutes les annotations de cette image ? L’image originale reste intacte.')) return;
                    data.paths = [];
                    data.rects = [];
                    data.comments = [];
                    render();
                });

                form.addEventListener('submit', () => {
                    input.value = JSON.stringify(data);
                });

                modal.addEventListener('shown.bs.modal', () => {
                    resize();
                });

                if (image.complete) resize();
                else image.addEventListener('load', resize);
                window.addEventListener('resize', resize);
            });
        });
    </script>
@endpush
