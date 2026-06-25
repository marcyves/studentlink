<x-mail::message>
# Demande d'accès professeur

**{{ $request->name }}** souhaite un compte enseignant sur StudentLink.

| | |
|---|---|
| **E-mail** | {{ $request->email }} |
| **Établissement** | {{ $request->institution ?? '—' }} |
| **Date** | {{ $request->created_at->format('d/m/Y H:i') }} |

@if ($request->message)
**Message :**

{{ $request->message }}
@endif

Créez le compte professeur depuis l'administration ou via :

`php artisan studentlink:create-professor {{ $request->email }} "{{ $request->name }}"`

<x-mail::subcopy>
Demande #{{ $request->id }} — en attente de traitement.
</x-mail::subcopy>
</x-mail::message>
