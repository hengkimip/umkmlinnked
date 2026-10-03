{{-- $stats: array of ['value' => int, 'label' => string, 'highlight' => bool] --}}
<section class="ib-stats ib-wrap" aria-label="Ringkasan data">
    <dl class="ib-stats__grid" data-count="{{ count($stats) }}" style="--stat-cols: {{ count($stats) }}">
        @foreach ($stats as $s)
            <div class="ib-stats__item">
                <dt class="ib-stats__label">{{ $s['label'] }}</dt>
                <dd @class(['ib-stats__number', 'ib-stats__number--gold' => $s['highlight'] ?? false])>
                    {{ number_format($s['value'], 0, ',', '.') }}
                </dd>
            </div>
        @endforeach
    </dl>
</section>
