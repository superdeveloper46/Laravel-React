"Name","E-mail","Campaigns","Leads","Response Time"
@foreach($companies as $lead)
"{{$lead->name}}","{{$lead->email}}","{{$lead->deals_count}}","{{$lead->leads_count}}","{{$lead->avg_lead_response}}"
@endforeach
