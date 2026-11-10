@extends('layouts.app')

@section('title', $exercise->title)
@section('topbar-title', 'Exercice')

@section('content')
    @php
        $isText = $exercise->type === \App\Enums\ExerciseType::Text;
        $latestGrade = $latestSubmission?->latestGrade;
    @endphp

    <div class="page-header">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-title mb-0">{{ $exercise->title }}</h1>
                <span class="badge text-bg-light">{{ $exercise->type->shortLabel() }}</span>
                @if ($latestSubmission)
                    <x-status-badge :status="$latestSubmission->status" />
                @endif
            </div>
            <p class="page-subtitle mb-0">{{ $exercise->theme?->subject?->name }} — {{ $exercise->theme?->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('student.exercises.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            @if ($latestSubmission)
                <a href="{{ route('student.submissions.show', $latestSubmission) }}" class="btn btn-outline-primary btn-sm">Ma soumission</a>
            @endif
        </div>
    </div>

    @if ($exercise->isOverdue() && ! $latestSubmission)
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="bi bi-alarm-fill mt-1"></i>
            <div>La date limite est dépassée. Vous pouvez encore envoyer une réponse, elle sera signalée comme rendue en retard.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header">Consignes</div>
                <div class="card-body">
                    <p class="small mb-3" style="white-space: pre-line">{{ $exercise->description ?: 'Aucune consigne complémentaire.' }}</p>
                    <dl class="detail-list">
                        <div class="detail-row"><dt>Barème</dt><dd>{{ $exercise->max_points }} points (converti sur 20)</dd></div>
                        <div class="detail-row"><dt>Échéance</dt><dd class="{{ $exercise->isOverdue() ? 'text-danger' : '' }}">{{ $exercise->due_at?->format('d/m/Y H:i') ?? 'Aucune' }}</dd></div>
                        <div class="detail-row"><dt>Type</dt><dd>{{ $exercise->type->label() }}</dd></div>
                    </dl>

                    @if ($exercise->attachments->isNotEmpty())
                        <div class="divider"></div>
                        <div class="small fw-semibold mb-2">Document(s) fourni(s)</div>
                        @foreach ($exercise->attachments as $attachment)
                            <a href="{{ route('files.exercise', $attachment) }}" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 py-1 text-reset">
                                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                                <span class="small text-truncate">{{ $attachment->original_name }}</span>
                                <span class="text-muted text-tiny ms-auto">{{ number_format($attachment->size / 1024, 0) }} Ko</span>
                                <i class="bi bi-download text-muted"></i>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>

            @if ($latestSubmission)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Ma dernière réponse</span>
                        <span class="text-muted text-tiny">Version {{ $latestSubmission->version }} — {{ $latestSubmission->submitted_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="card-body">
                        @if ($latestSubmission->is_late)
                            <div class="alert alert-warning py-2 small"><i class="bi bi-clock-history me-1"></i>Cette version a été envoyée après l’échéance.</div>
                        @endif

                        @if ($isText)
                            <div class="soft-panel no-copy" data-no-copy>
                                <div class="text-muted text-tiny mb-2"><i class="bi bi-lock me-1"></i>Réponse protégée — copie et menu contextuel désactivés</div>
                                <div style="white-space: pre-wrap">{{ $latestSubmission->text_answer }}</div>
                            </div>
                        @elseif ($latestSubmission->files->isNotEmpty())
                            <div class="row g-2">
                                @foreach ($latestSubmission->files as $file)
                                    <div class="col-6 col-md-4">
                                        <a href="{{ route('files.submission', $file) }}" target="_blank" class="d-block border rounded p-1">
                                            <img src="{{ route('files.submission', $file) }}" alt="Copie {{ $loop->iteration }}" class="img-fluid w-100" style="height:110px;object-fit:cover">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($latestSubmission->status === \App\Enums\SubmissionStatus::RevisionRequested)
                            <div class="alert alert-info py-2 small mt-3 mb-0">
                                <i class="bi bi-arrow-repeat me-1"></i>Votre superviseur demande une nouvelle réponse. Consultez sa remarque puis envoyez une nouvelle version ci-dessous.
                                @if ($latestSubmission->revision_comment)
                                    <div class="mt-1">{{ $latestSubmission->revision_comment }}</div>
                                @endif
                            </div>
                        @elseif ($latestGrade)
                            <div class="divider"></div>
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                <div class="fw-semibold">Correction</div>
                                <span class="badge text-bg-success">{{ $latestGrade->score }} / {{ $latestGrade->max_points }} — {{ number_format($latestGrade->convertedTo20(), 2, ',', ' ') }}/20</span>
                            </div>
                            @if ($latestGrade->comment)
                                <p class="small mb-0" style="white-space: pre-line">{{ $latestGrade->comment }}</p>
                            @else
                                <p class="small text-muted mb-0">Aucun commentaire écrit.</p>
                            @endif
                        @else
                            <div class="text-muted small mt-2"><i class="bi bi-hourglass-split me-1"></i>En attente de correction.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">{{ $latestSubmission ? 'Envoyer une nouvelle version' : 'Envoyer ma réponse' }}</div>
                <div class="card-body">
                    @if (! $canSubmit)
                        <p class="small text-muted mb-0">
                            <i class="bi bi-check2-circle text-success me-1"></i>
                            Votre réponse a été envoyée. Une nouvelle version sera possible uniquement si votre superviseur en fait la demande.
                        </p>
                    @else
                        <form method="POST" action="{{ route('student.exercises.submit', $exercise) }}" enctype="multipart/form-data">
                            @csrf

                            @if ($isText)
                                <x-textarea name="text_answer" label="Ma réponse" :rows="10" required no-copy help="La sélection, la copie et le collage sont désactivés dans cette zone." />
                            @else
                                <div class="mb-3">
                                    <label for="files" class="form-label">Photos de ma copie <span class="text-danger">*</span></label>
                                    <input type="file" name="files[]" id="files" class="form-control @error('files') is-invalid @enderror @error('files.*') is-invalid @enderror"
                                           accept="image/jpeg,image/png,image/webp" multiple required data-file-preview="#filesPreview">
                                    <div class="form-text">JPG, JPEG, PNG ou WEBP — 10 images maximum, 10 Mo par image.</div>
                                    @error('files')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @error('files.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    <div id="filesPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-1"></i>Envoyer ma réponse
                            </button>
                            <p class="form-text text-center mb-0 mt-2">Vérifiez votre travail avant l’envoi : chaque envoi crée une version conservée.</p>
                        </form>
                    @endif
                </div>
            </div>

            @if ($history->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header">Historique de mes envois</div>
                    <div class="card-body p-0">
                        @foreach ($history as $version)
                            <a href="{{ route('student.submissions.show', $version) }}" class="d-flex justify-content-between gap-2 px-3 py-2 text-reset {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <div>
                                    <div class="small fw-semibold">Version {{ $version->version }}</div>
                                    <div class="text-muted text-tiny">{{ $version->submitted_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="text-end">
                                    <x-status-badge :status="$version->status" />
                                    @if ($version->latestGrade)
                                        <div class="text-tiny mono-num mt-1">{{ $version->latestGrade->score }} / {{ $version->latestGrade->max_points }}</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
