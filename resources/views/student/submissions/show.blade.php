@extends('layouts.app')

@section('title', 'Ma soumission')
@section('topbar-title', 'Ma soumission')

@section('content')
    @php
        $exercise = $submission->exercise;
        $isText = $exercise?->type === \App\Enums\ExerciseType::Text;
        $grade = $submission->latestGrade;
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">Ma soumission</h1>
                <x-status-badge :status="$submission->status" />
                @if ($submission->is_late)<span class="badge text-bg-warning">En retard</span>@endif
            </div>
            <p class="page-subtitle mb-0">{{ $exercise?->title }} — {{ $exercise?->theme?->subject?->name }} / {{ $exercise?->theme?->name }}</p>
        </div>
        <div class="page-actions">
            @if ($exercise)
                <a href="{{ route('student.exercises.show', $exercise) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour à l’exercice</a>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Version {{ $submission->version }}</span>
                    <span class="text-muted text-tiny">{{ $submission->submitted_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="card-body">
                    @if ($isText)
                        <div class="soft-panel no-copy" data-no-copy>
                            <div class="text-muted text-tiny mb-2"><i class="bi bi-lock me-1"></i>Réponse protégée — sélection, copie et menu contextuel désactivés</div>
                            <div style="white-space: pre-wrap">{{ $submission->text_answer }}</div>
                        </div>
                    @elseif ($submission->files->isEmpty())
                        <p class="small text-muted mb-0">Aucune image jointe à cette version.</p>
                    @else
                        <div class="row g-2">
                            @foreach ($submission->files as $file)
                                @php
                                    $annotationData = ['paths' => [], 'rects' => [], 'comments' => []];
                                    foreach ($file->annotations as $annotation) {
                                        $annotationRowData = is_array($annotation->data) ? $annotation->data : [];
                                        foreach (['paths', 'rects', 'comments'] as $key) {
                                            $annotationData[$key] = array_merge($annotationData[$key], $annotationRowData[$key] ?? []);
                                        }
                                    }
                                    $hasAnnotations = ! empty($annotationData['paths']) || ! empty($annotationData['rects']) || ! empty($annotationData['comments']);
                                    $fileComment = $file->annotations->firstWhere('comment', '!=', null)?->comment;
                                @endphp
                                <div class="col-6 col-md-4">
                                    <div class="border rounded p-1 h-100 d-flex flex-column">
                                        <a href="{{ route('files.submission', $file) }}" target="_blank" class="d-block">
                                            <img src="{{ route('files.submission', $file) }}" alt="Copie {{ $loop->iteration }}" class="img-fluid w-100" style="height:130px;object-fit:cover">
                                        </a>
                                        <div class="d-flex gap-1 mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#copyModal-{{ $file->id }}">
                                                <i class="bi bi-eye me-1"></i>Voir {{ $hasAnnotations ? 'annotée' : '' }}
                                            </button>
                                            <a href="{{ route('files.submission', $file) }}" target="_blank" class="btn btn-sm btn-outline-secondary" aria-label="Ouvrir en plein écran"><i class="bi bi-arrows-fullscreen"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="copyModal-{{ $file->id }}" tabindex="-1" aria-labelledby="copyModalLabel-{{ $file->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header py-2">
                                                <h2 class="modal-title fs-6" id="copyModalLabel-{{ $file->id }}">{{ $file->original_name }}</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="annotation-stage" data-readonly-annotation>
                                                    <img src="{{ route('files.submission', $file) }}" alt="Copie annotée">
                                                    <canvas aria-label="Annotations du superviseur"></canvas>
                                                    <div class="annotation-pins"></div>
                                                </div>
                                                <script type="application/json" class="annotation-data">@json($annotationData)</script>

                                                @if ($fileComment)
                                                    <div class="alert alert-light border mt-3 mb-0 small">
                                                        <i class="bi bi-chat-left-text me-1"></i>{{ $fileComment }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="form-text mt-3 mb-0">Les annotations du superviseur sont affichées sur un calque séparé ; votre image d’origine reste inchangée.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Mes envois pour cet exercice</div>
                <div class="card-body p-0">
                    @foreach ($history as $version)
                        <a href="{{ route('student.submissions.show', $version) }}" class="d-flex justify-content-between gap-2 px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }} {{ $version->id === $submission->id ? 'bg-primary-soft' : '' }}">
                            <div>
                                <div class="small fw-semibold">Version {{ $version->version }} @if ($version->id === $submission->id)<span class="badge text-bg-primary">Actuelle</span>@endif</div>
                                <div class="meta-line">
                                    <span>{{ $version->submitted_at->format('d/m/Y H:i') }}</span>
                                    <span><x-status-badge :status="$version->status" /></span>
                                </div>
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
                <div class="card-header">Note et correction</div>
                <div class="card-body">
                    @if ($grade)
                        <div class="d-flex align-items-baseline gap-2 mb-2">
                            <span class="stat-value">{{ $grade->score }} / {{ $grade->max_points }}</span>
                        </div>
                        <div class="badge text-bg-success mb-3">{{ number_format($grade->convertedTo20(), 2, ',', ' ') }} /20</div>
                        <div class="divider"></div>
                        <div class="small fw-semibold mb-1">Commentaire du superviseur</div>
                        <p class="small mb-0" style="white-space: pre-line">{{ $grade->comment ?: 'Aucun commentaire écrit.' }}</p>
                        <div class="text-muted text-tiny mt-2">Corrigée le {{ $grade->graded_at->format('d/m/Y H:i') }} par {{ $grade->grader?->name ?? 'Superviseur' }}</div>
                    @elseif ($submission->status === \App\Enums\SubmissionStatus::RevisionRequested)
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="bi bi-arrow-repeat me-1"></i>Une nouvelle réponse vous est demandée. Rendez-vous sur l’exercice pour envoyer une nouvelle version.
                            @if ($submission->revision_comment)
                                <div class="mt-2"><strong>Remarque :</strong> {{ $submission->revision_comment }}</div>
                            @endif
                        </div>
                    @else
                        <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>Votre copie est en attente de correction.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Détails</div>
                <div class="card-body">
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Exercice</dt><dd>{{ $exercise?->title ?? '—' }}</dd></div>
                        <div class="detail-row"><dt>Version</dt><dd>{{ $submission->version }}</dd></div>
                        <div class="detail-row"><dt>Envoyée le</dt><dd>{{ $submission->submitted_at->format('d/m/Y H:i') }}</dd></div>
                        <div class="detail-row"><dt>En retard</dt><dd>{{ $submission->is_late ? 'Oui' : 'Non' }}</dd></div>
                    </dl>

                    @if ($exercise && $submission->status === \App\Enums\SubmissionStatus::RevisionRequested)
                        <div class="divider"></div>
                        <a href="{{ route('student.exercises.show', $exercise) }}" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Envoyer une nouvelle réponse</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const colors = { draw: '#b42318', highlight: 'rgba(250, 204, 21, 0.35)' };

            document.querySelectorAll('[data-readonly-annotation]').forEach((stage) => {
                const image = stage.querySelector('img');
                const canvas = stage.querySelector('canvas');
                const pins = stage.querySelector('.annotation-pins');
                const dataElement = stage.parentElement.querySelector('.annotation-data');
                if (!image || !canvas || !dataElement) return;

                const data = JSON.parse(dataElement.textContent || '{"paths":[],"rects":[],"comments":[]}');
                data.paths ||= [];
                data.rects ||= [];
                data.comments ||= [];
                const context = canvas.getContext('2d');
                let scaleX = 1;

                const render = () => {
                    const rect = image.getBoundingClientRect();
                    if (!rect.width || !rect.height) return;

                    canvas.width = Math.round(rect.width * (window.devicePixelRatio || 1));
                    canvas.height = Math.round(rect.height * (window.devicePixelRatio || 1));
                    canvas.style.width = rect.width + 'px';
                    canvas.style.height = rect.height + 'px';
                    scaleX = canvas.width / rect.width;

                    context.clearRect(0, 0, canvas.width, canvas.height);

                    data.rects.forEach((item) => {
                        context.fillStyle = item.color || colors.highlight;
                        context.fillRect(item.x * canvas.width, item.y * canvas.height, item.w * canvas.width, item.h * canvas.height);
                        context.strokeStyle = 'rgba(180, 130, 10, .8)';
                        context.lineWidth = scaleX;
                        context.strokeRect(item.x * canvas.width, item.y * canvas.height, item.w * canvas.width, item.h * canvas.height);
                    });

                    data.paths.forEach((path) => {
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
                    });

                    if (pins) {
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
                    }
                };

                const modal = stage.closest('.modal');
                modal?.addEventListener('shown.bs.modal', render);
                if (image.complete) render();
                else image.addEventListener('load', render);
                window.addEventListener('resize', render);
            });
        });
    </script>
@endpush
