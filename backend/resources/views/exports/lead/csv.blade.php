FullName, Phone, Email, Status;
{{ $lead->fullname  }}, {{ $lead->phone  }}, {{ $lead->email }}, {{ $lead->statusInfo->name }};

Message,Author,Date;
@foreach($lead->leadNotes as $note)
{{ $note->message }},{{ $note->agent->name }},{{ $note->created_at->format('d/m/Y') }};
@endforeach