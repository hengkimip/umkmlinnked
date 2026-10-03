@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-navy-700 focus:ring-navy-700 rounded-lg shadow-sm text-sm']) }}>
