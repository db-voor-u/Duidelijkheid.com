@component('mail::message')
    # Nieuw contactbericht

    **Naam:** {{ $m->name }}
    **E-mail:** {{ $m->email }}
    @isset($m->phone)
        **Telefoon:** {{ $m->phone }}
    @endisset

    **Onderwerp:** {{ $m->subject }}

    **Bericht:**
    {!! $m->message !!}

    @component('mail::panel')
        Status: {{ $m->status }}
        IP: {{ $m->ip }}
        Datum: {{ optional($m->created_at)->format('d-m-Y H:i:s') }}
    @endcomponent

    @component('mail::button', ['url' => url('/hoofdbeheerder/contact')])
        Open inbox
    @endcomponent

@endcomponent
