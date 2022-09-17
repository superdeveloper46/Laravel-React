"Leads: period ({{$reportPayload->startDate}}-{{$reportPayload->endDate}})";
"";
"Date","Status","Full Name","E-mail","Phone", "Company", "Source", "Metadata";
@foreach($leads as $lead)
"{{$lead->created_at->format('d/m/Y')}}","{{$lead->statusInfo->name}}","{{$lead->fullname}}","{{$lead->email}}", "{{$lead->phone}}", "{{$lead->company['name']}}", "{{$lead->campaign['name']}}", "{{$lead->metadata}}",;
@endforeach
